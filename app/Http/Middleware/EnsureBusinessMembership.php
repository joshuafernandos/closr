<?php

namespace App\Http\Middleware;

use App\Enums\BusinessRole;
use App\Models\Business;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessMembership
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $minimumRole = null): Response
    {
        [$user, $business] = [$request->user(), $this->business($request)];

        abort_if(! $user || ! $business || ! $user->belongsToBusiness($business), 403);

        $this->ensureBusinessMemberHasRequiredRole($user, $business, $minimumRole);

        return $next($request);
    }

    /**
     * Ensure the given user has at least the given role, if applicable.
     */
    protected function ensureBusinessMemberHasRequiredRole(User $user, Business $business, ?string $minimumRole): void
    {
        if ($minimumRole === null) {
            return;
        }

        $role = $user->businessRole($business);

        $requiredRole = BusinessRole::tryFrom($minimumRole);

        abort_if(
            $requiredRole === null ||
            $role === null ||
            ! $role->isAtLeast($requiredRole),
            403,
        );
    }

    /**
     * Get the business associated with the request.
     */
    protected function business(Request $request): ?Business
    {
        $business = $request->route('business');

        if (is_string($business)) {
            $business = Business::where('slug', $business)->first();
        }

        return $business;
    }
}
