<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Models\User;
use App\Support\OrganizationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Puts a signed-in request inside one organization.
 *
 * Which one: the session's choice if the user may be there, otherwise their first active
 * membership (owner levels first), otherwise a personal organization is created so the console
 * always has somewhere to land. The platform owner (users.is_admin) may be in any organization
 * and, with none chosen, is sent to the organization list instead.
 *
 * While the context is set, OrganizationScope narrows every organization-owned model, so a
 * query in the console cannot reach another organization's rows even if it forgets to filter.
 */
class SetOrganizationContext
{
    private const LEVEL_ORDER = [
        Organization::LEVEL_OWNER => 0,
        Organization::LEVEL_ADMIN => 1,
        Organization::LEVEL_MEMBER => 2,
    ];

    public function handle(Request $request, Closure $next): Response
    {
        OrganizationContext::clear();

        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $organization = $this->resolve($request, $user);

        if (! $organization) {
            // Only the platform owner can get here: everyone else is provisioned one above.
            return redirect()->route('admin.organizations');
        }

        if ($organization->isSuspended() && ! $user->isAdmin()) {
            abort(403, __('messages.organization_suspended'));
        }

        OrganizationContext::set($organization->id);
        $request->attributes->set('organization', $organization);
        view()->share('currentOrganization', $organization);

        return $next($request);
    }

    /** Never leave a context behind for the next request in a long-lived worker. */
    public function terminate(Request $request, Response $response): void
    {
        OrganizationContext::clear();
    }

    private function resolve(Request $request, User $user): ?Organization
    {
        $chosen = $request->session()->get('organization_id');

        if ($chosen) {
            $organization = Organization::find($chosen);

            if ($organization && ($user->isAdmin() || $organization->levelFor($user) !== null)) {
                return $organization;
            }

            $request->session()->forget('organization_id');
        }

        $memberships = $user->organizations()->get()
            ->sortBy(fn ($org) => [
                $org->isSuspended() ? 1 : 0,
                self::LEVEL_ORDER[$org->pivot->level] ?? 3,
            ])
            ->values();

        if ($memberships->isNotEmpty()) {
            return $memberships->first();
        }

        if ($user->isAdmin()) {
            return null;
        }

        return Organization::provisionPersonalFor($user);
    }
}
