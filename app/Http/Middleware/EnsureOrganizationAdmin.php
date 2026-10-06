<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Organization-admin gate. Runs after SetOrganizationContext: the organization's own owner and
 * admins pass, and so does the platform owner. Plain members are read-only in the console.
 */
class EnsureOrganizationAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $organization = $request->attributes->get('organization');

        $allowed = $user
            && $organization instanceof Organization
            && ($user->isAdmin() || $organization->isManagedBy($user));

        if (! $allowed) {
            if ($request->expectsJson()) {
                return response()->json(['error' => __('messages.not_authorized')], 403);
            }

            return redirect()->route('organization.console')->with('error', __('messages.not_authorized'));
        }

        return $next($request);
    }
}
