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

/**
 * The platform owner's view of every organization (tenant): create, rename, suspend, move
 * schedules in, and step into one to see exactly what its admin sees.
 *
 * Routed inside the `admin` middleware group, which also gates on a recently confirmed password
 * and two-factor, so the checks here are defense in depth, the same as AdminController's.
 */
class AdminOrganizationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizePlatformOwner();

        $search = trim((string) $request->query('q', ''));

        $organizations = Organization::query()
            ->with('owner:id,name,email')
            ->withCount(['users', 'schedules'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $unassignedSchedules = Role::whereNull('organization_id')->where('is_deleted', false)->count();

        return view('admin.organizations.index', compact('organizations', 'search', 'unassignedSchedules'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePlatformOwner();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'owner_email' => ['required', 'email', 'max:255'],
        ]);

        $owner = User::where('email', strtolower($validated['owner_email']))->first();

        if (! $owner) {
            return back()->withInput()->with('error', __('messages.organization_owner_not_found'));
        }

        $organization = Organization::createWithOwner($validated['name'], $owner);

        AuditService::log(
            AuditService::ORGANIZATION_CREATE,
            auth()->id(),
            Organization::class,
            $organization->id,
            null,
            ['name' => $organization->name, 'owner_id' => $owner->id],
        );

        return redirect()->route('admin.organizations.show', UrlUtils::encodeId($organization->id))
            ->with('success', __('messages.organization_created'));
    }

    public function show(string $organization): View
    {
        $this->authorizePlatformOwner();

        $organization = $this->find($organization)->load('owner:id,name,email');

        $members = $organization->users()->orderBy('name')->get();
        $schedules = $organization->schedules()
            ->where('is_deleted', false)
            ->orderBy('name')
            ->get(['id', 'name', 'subdomain', 'type']);

        return view('admin.organizations.show', compact('organization', 'members', 'schedules'));
    }

    public function update(Request $request, string $organization): RedirectResponse
    {
        $this->authorizePlatformOwner();

        $organization = $this->find($organization);
        $validated = $request->validate(['name' => ['required', 'string', 'max:100']]);

        $old = ['name' => $organization->name];
        $organization->update(['name' => $validated['name']]);

        AuditService::log(
            AuditService::ORGANIZATION_UPDATE,
            auth()->id(),
            Organization::class,
            $organization->id,
            $old,
            ['name' => $organization->name],
        );

        return back()->with('success', __('messages.organization_updated'));
    }

    public function suspend(string $organization): RedirectResponse
    {
        $this->authorizePlatformOwner();

        $organization = $this->find($organization);
        $organization->suspend();

        AuditService::log(AuditService::ORGANIZATION_SUSPEND, auth()->id(), Organization::class, $organization->id);

        return back()->with('success', __('messages.organization_suspended_notice'));
    }

    public function resume(string $organization): RedirectResponse
    {
        $this->authorizePlatformOwner();

        $organization = $this->find($organization);
        $organization->resume();

        AuditService::log(AuditService::ORGANIZATION_RESUME, auth()->id(), Organization::class, $organization->id);

        return back()->with('success', __('messages.organization_resumed'));
    }

    /**
     * Move a schedule into this organization. Done with a query-builder update on purpose: Role's
     * model events do heavy work (translations, calendar sync) that a plain ownership change
     * must not trigger.
     */
    public function assignSchedule(Request $request, string $organization): RedirectResponse
    {
        $this->authorizePlatformOwner();

        $organization = $this->find($organization);
        $validated = $request->validate(['subdomain' => ['required', 'string', 'max:255']]);

        $role = Role::where('subdomain', strtolower(trim($validated['subdomain'])))
            ->where('is_deleted', false)
            ->first();

        if (! $role) {
            return back()->withInput()->with('error', __('messages.organization_schedule_not_found'));
        }

        $previous = $role->organization_id;

        Role::whereKey($role->id)->update(['organization_id' => $organization->id]);

        AuditService::log(
            AuditService::ORGANIZATION_ASSIGN_SCHEDULE,
            auth()->id(),
            Role::class,
            $role->id,
            ['organization_id' => $previous],
            ['organization_id' => $organization->id],
            $role->subdomain,
        );

        return back()->with('success', __('messages.organization_schedule_assigned'));
    }

    /** Step into an organization: the console then shows what its own admin would see. */
    public function switch(string $organization): RedirectResponse
    {
        $this->authorizePlatformOwner();

        $organization = $this->find($organization);
        session(['organization_id' => $organization->id]);

        return redirect()->route('organization.console');
    }

    private function find(string $hash): Organization
    {
        return Organization::findOrFail(UrlUtils::decodeIdOrFail($hash));
    }

    private function authorizePlatformOwner(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }
}
