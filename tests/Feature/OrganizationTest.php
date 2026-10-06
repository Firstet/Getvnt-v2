<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Role;
use App\Support\OrganizationContext;
use App\Utils\UrlUtils;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Concerns\CreatesScheduleData;
use Tests\TestCase;

/**
 * The organization (tenant) layer: isolation by OrganizationScope, the organization console with
 * its owner/admin/member levels, and the platform owner's control over every organization.
 */
class OrganizationTest extends TestCase
{
    use CreatesScheduleData;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        OrganizationContext::clear();

        parent::tearDown();
    }

    private function requireRoutes(): void
    {
        if (! Route::has('organization.console') || ! Route::has('admin.organizations')) {
            $this->markTestSkipped('Organization routes are not registered in this environment.');
        }
    }

    /** An organization with its owner and one schedule inside it. */
    private function organizationWithSchedule(string $scheduleName): array
    {
        $owner = $this->createOwner();
        $organization = Organization::createWithOwner($scheduleName.' Org', $owner);
        $role = $this->createRole($owner, 'venue', ['name' => $scheduleName]);
        Role::whereKey($role->id)->update(['organization_id' => $organization->id]);

        return [$owner, $organization, $role];
    }

    private function actingAsPlatformOwner(): self
    {
        $this->requireRoutes();

        return $this->withSession(['admin_password_confirmed_at' => now()->timestamp])
            ->actingAs($this->createOwner(true));
    }

    public function test_scope_narrows_roles_only_while_a_context_is_set(): void
    {
        [, $orgA, $roleA] = $this->organizationWithSchedule('Alpha Hall');
        [, , $roleB] = $this->organizationWithSchedule('Beta Club');

        $this->assertEqualsCanonicalizing(
            [$roleA->id, $roleB->id],
            Role::whereIn('id', [$roleA->id, $roleB->id])->pluck('id')->all(),
            'With no context nothing is restricted, so the guest portal and jobs behave as before.'
        );

        OrganizationContext::set($orgA->id);

        $this->assertSame([$roleA->id], Role::whereIn('id', [$roleA->id, $roleB->id])->pluck('id')->all());
        $this->assertNull(Role::find($roleB->id), 'A direct lookup of another organization row finds nothing.');

        OrganizationContext::clear();
    }

    public function test_a_role_created_inside_a_context_is_stamped_with_it(): void
    {
        $owner = $this->createOwner();
        $organization = Organization::createWithOwner('Stamp Org', $owner);

        $role = OrganizationContext::run($organization->id, fn () => $this->createRole($owner, 'venue', ['name' => 'Stamped']));

        $this->assertSame($organization->id, Role::withoutGlobalScopes()->find($role->id)->organization_id);
        $this->assertNull(OrganizationContext::id(), 'run() restores the previous context.');
    }

    public function test_console_lists_only_the_members_own_organization(): void
    {
        $this->requireRoutes();

        [$ownerA] = $this->organizationWithSchedule('Alpha Hall');
        $this->organizationWithSchedule('Beta Club');

        $this->actingAs($ownerA)
            ->get(route('organization.console'))
            ->assertOk()
            ->assertSee('Alpha Hall')
            ->assertDontSee('Beta Club');
    }

    public function test_a_user_with_no_organization_is_given_a_personal_one(): void
    {
        $this->requireRoutes();

        $user = $this->createOwner();

        $this->actingAs($user)->get(route('organization.console'))->assertOk();

        $this->assertSame(1, $user->organizations()->count());
        $this->assertSame(Organization::LEVEL_OWNER, $user->organizations()->first()->pivot->level);
    }

    public function test_an_organization_admin_can_add_a_member_but_a_plain_member_cannot(): void
    {
        $this->requireRoutes();

        [$owner, $organization] = $this->organizationWithSchedule('Alpha Hall');
        $plain = $this->createOwner();
        $newcomer = $this->createOwner();
        $organization->users()->attach($plain->id, ['level' => Organization::LEVEL_MEMBER]);

        $this->actingAs($plain)
            ->post(route('organization.members.add'), ['email' => $newcomer->email, 'level' => 'member'])
            ->assertRedirect(route('organization.console'));
        $this->assertNull($organization->fresh()->levelFor($newcomer));

        $this->actingAs($owner)
            ->post(route('organization.members.add'), ['email' => $newcomer->email, 'level' => 'admin'])
            ->assertSessionHas('success');
        $this->assertSame(Organization::LEVEL_ADMIN, $organization->fresh()->levelFor($newcomer));
    }

    public function test_the_owner_cannot_be_removed_or_demoted(): void
    {
        $this->requireRoutes();

        [$owner, $organization] = $this->organizationWithSchedule('Alpha Hall');
        $admin = $this->createOwner();
        $organization->users()->attach($admin->id, ['level' => Organization::LEVEL_ADMIN]);

        $hash = UrlUtils::encodeId($owner->id);

        $this->actingAs($admin)->delete(route('organization.members.remove', $hash))->assertSessionHas('error');
        $this->actingAs($admin)->put(route('organization.members.update', $hash), ['level' => 'member'])->assertSessionHas('error');

        $this->assertSame(Organization::LEVEL_OWNER, $organization->fresh()->levelFor($owner));
    }

    public function test_a_user_cannot_switch_into_an_organization_they_do_not_belong_to(): void
    {
        $this->requireRoutes();

        [$ownerA] = $this->organizationWithSchedule('Alpha Hall');
        [, $orgB] = $this->organizationWithSchedule('Beta Club');

        $this->actingAs($ownerA)
            ->post(route('organization.switch', UrlUtils::encodeId($orgB->id)))
            ->assertForbidden();
    }

    public function test_a_suspended_organization_is_blocked_for_its_members_but_not_the_platform_owner(): void
    {
        $this->requireRoutes();

        [$owner, $organization] = $this->organizationWithSchedule('Alpha Hall');
        $organization->suspend();

        $this->actingAs($owner)->get(route('organization.console'))->assertForbidden();

        $this->actingAsPlatformOwner()
            ->post(route('admin.organizations.switch', UrlUtils::encodeId($organization->id)))
            ->assertRedirect(route('organization.console'));
    }

    public function test_the_platform_owner_can_create_suspend_resume_and_assign_schedules(): void
    {
        $owner = $this->createOwner();
        $loose = $this->createRole($owner, 'venue', ['name' => 'Loose Venue']);

        $this->actingAsPlatformOwner()
            ->post(route('admin.organizations.store'), ['name' => 'Acme Events', 'owner_email' => $owner->email])
            ->assertRedirect();

        $organization = Organization::where('name', 'Acme Events')->firstOrFail();
        $hash = UrlUtils::encodeId($organization->id);
        $this->assertSame(Organization::LEVEL_OWNER, $organization->levelFor($owner));

        $this->post(route('admin.organizations.assign_schedule', $hash), ['subdomain' => $loose->subdomain])
            ->assertSessionHas('success');
        $this->assertSame($organization->id, Role::withoutGlobalScopes()->find($loose->id)->organization_id);

        $this->post(route('admin.organizations.suspend', $hash));
        $this->assertTrue($organization->fresh()->isSuspended());

        $this->post(route('admin.organizations.resume', $hash));
        $this->assertTrue($organization->fresh()->isActive());
    }

    public function test_a_regular_user_cannot_reach_the_platform_organization_pages(): void
    {
        $this->requireRoutes();

        $this->actingAs($this->createOwner())
            ->get(route('admin.organizations'))
            ->assertRedirect();
    }
}
