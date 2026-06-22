<?php

namespace App\Http\Controllers\Businesses;

use App\Enums\BusinessRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Businesses\UpdateBusinessMemberRequest;
use App\Models\Business;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class BusinessMemberController extends Controller
{
    /**
     * Update the specified business member's role.
     */
    public function update(UpdateBusinessMemberRequest $request, Business $business, User $user): RedirectResponse
    {
        Gate::authorize('updateMember', $business);

        $newRole = BusinessRole::from($request->validated('role'));

        $business->memberships()
            ->where('user_id', $user->id)
            ->firstOrFail()
            ->update(['role' => $newRole]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member role updated.')]);

        return to_route('businesses.edit', ['business' => $business->slug]);
    }

    /**
     * Remove the specified business member.
     */
    public function destroy(Business $business, User $user): RedirectResponse
    {
        Gate::authorize('removeMember', $business);

        abort_if($business->owner()?->is($user), 403, __('The business owner cannot be removed.'));

        $business->memberships()
            ->where('user_id', $user->id)
            ->delete();

        if ($user->isCurrentBusiness($business)) {
            $user->switchBusiness($user->personalBusiness());
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member removed.')]);

        return to_route('businesses.edit', ['business' => $business->slug]);
    }
}
