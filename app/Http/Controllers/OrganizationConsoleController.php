<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use App\Utils\UrlUtils;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * An organization's own console: its schedules and its team.
 *
 * Every route runs behind SetOrganizationContext, so Role queries here are already narrowed to
 * the current organization by OrganizationScope. Mutating routes also sit behind
 * EnsureOrganizationAdmin, so plain members can look but not change anything.
 */
class OrganizationConsoleController extends Controller
{
    public function show(Request $request): View
    {
        $organization = $this->organization($request);
        $user = $request->user();

        $schedules = Role::query()
            ->where('is_deleted', false)
            ->orderBy('name')
            ->get(['id', 'name', 'subdomain', 'type']);

        $members = $organization->users()->orderBy('name')->get();
        $canManage = $user->isAdmin() || $organization->isManagedBy($user);
        $myOrganizations = $user->organizations()->orderBy('name')->get();

        return view('organization.console', compact(
            'organization', 'schedules', 'members', 'canManage', 'myOrganizations'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $organization = $this->organization($request);
        $validated = $request->validate(['name' => ['required', 'string', 'max:100']]);

        $old = ['name' => $organization->name];
        $organization->update(['name' => $validated['name']]);

        AuditService::log(
            AuditService::ORGANIZATION_UPDATE,
            $request->user()->id,
            Organization::class,
            $organization->id,
            $old,
            ['name' => $organization->name],
        );

        return back()->with('success', __('messages.organization_updated'));
    }

    /** Add an existing account to the team. Inviting people without an account is a later step. */
    public function addMember(Request $request): RedirectResponse
    {
        $organization = $this->organization($request);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'level' => ['required', Rule::in([Organization::LEVEL_ADMIN, Organization::LEVEL_MEMBER])],
        ]);

        $member = User::where('email', strtolower($validated['email']))->first();

        if (! $member) {
            return back()->withInput()->with('error', __('messages.organization_member_not_found'));
        }

        if ($organization->levelFor($member) !== null) {
            return back()->withInput()->with('error', __('messages.organization_member_exists'));
        }

        $organization->users()->attach($member->id, ['level' => $validated['level']]);

        AuditService::log(
            AuditService::ORGANIZATION_MEMBER_ADD,
            $request->user()->id,
            Organization::class,
            $organization->id,
            null,
            ['user_id' => $member->id, 'level' => $validated['level']],
        );

        return back()->with('success', __('messages.organization_member_added'));
    }

    public function updateMember(Request $request, string $user): RedirectResponse
    {
        $organization = $this->organization($request);
        $validated = $request->validate([
            'level' => ['required', Rule::in([Organization::LEVEL_ADMIN, Organization::LEVEL_MEMBER])],
        ]);

        $member = $this->member($organization, $user);

        if ($organization->levelFor($member) === Organization::LEVEL_OWNER) {
            return back()->with('error', __('messages.organization_owner_locked'));
        }

        $old = $organization->levelFor($member);
        $organization->users()->updateExistingPivot($member->id, ['level' => $validated['level']]);

        AuditService::log(
            AuditService::ORGANIZATION_MEMBER_UPDATE,
            $request->user()->id,
            Organization::class,
            $organization->id,
            ['user_id' => $member->id, 'level' => $old],
            ['user_id' => $member->id, 'level' => $validated['level']],
        );

        return back()->with('success', __('messages.organization_member_updated'));
    }

    public function removeMember(Request $request, string $user): RedirectResponse
    {
        $organization = $this->organization($request);
        $member = $this->member($organization, $user);

        if ($organization->levelFor($member) === Organization::LEVEL_OWNER) {
            return back()->with('error', __('messages.organization_owner_locked'));
        }

        $organization->users()->detach($member->id);

        AuditService::log(
            AuditService::ORGANIZATION_MEMBER_REMOVE,
            $request->user()->id,
            Organization::class,
            $organization->id,
            ['user_id' => $member->id],
            null,
        );

        return back()->with('success', __('messages.organization_member_removed'));
    }

    /** Switch between the organizations the user belongs to. The platform owner may enter any. */
    public function switch(Request $request, string $organization): RedirectResponse
    {
        $target = Organization::findOrFail(UrlUtils::decodeIdOrFail($organization));
        $user = $request->user();

        abort_unless($user->isAdmin() || $target->levelFor($user) !== null, 403);

        $request->session()->put('organization_id', $target->id);

        return redirect()->route('organization.console');
    }

    private function organization(Request $request): Organization
    {
        return $request->attributes->get('organization');
    }

    private function member(Organization $organization, string $hash): User
    {
        $id = UrlUtils::decodeIdOrFail($hash);

        return $organization->users()->where('users.id', $id)->firstOrFail();
    }
}
