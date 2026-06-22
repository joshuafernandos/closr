<?php

namespace App\Http\Controllers\Businesses;

use App\Enums\BusinessRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Businesses\CreateBusinessInvitationRequest;
use App\Http\Requests\Businesses\RespondToBusinessInvitationRequest;
use App\Models\Business;
use App\Models\BusinessInvitation;
use App\Notifications\Businesses\BusinessInvitation as BusinessInvitationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class BusinessInvitationController extends Controller
{
    /**
     * Store a newly created invitation.
     */
    public function store(CreateBusinessInvitationRequest $request, Business $business): RedirectResponse
    {
        Gate::authorize('inviteMember', $business);

        $invitation = $business->invitations()->create([
            'email' => $request->validated('email'),
            'role' => BusinessRole::from($request->validated('role')),
            'invited_by' => $request->user()->id,
            'expires_at' => now()->addDays(3),
        ]);

        Notification::route('mail', $invitation->email)
            ->notify(new BusinessInvitationNotification($invitation));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation sent.')]);

        return to_route('businesses.edit', ['business' => $business->slug]);
    }

    /**
     * Cancel the specified invitation.
     */
    public function destroy(Business $business, BusinessInvitation $invitation): RedirectResponse
    {
        abort_unless($invitation->business_id === $business->id, 404);

        Gate::authorize('cancelInvitation', $business);

        $invitation->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation cancelled.')]);

        return to_route('businesses.edit', ['business' => $business->slug]);
    }

    /**
     * Accept the invitation.
     */
    public function accept(RespondToBusinessInvitationRequest $request, BusinessInvitation $invitation): RedirectResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($user, $invitation) {
            $business = $invitation->business;

            $business->memberships()->firstOrCreate(
                ['user_id' => $user->id],
                ['role' => $invitation->role],
            );

            $invitation->update(['accepted_at' => now()]);

            $user->switchBusiness($business);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation accepted.')]);

        return to_route('dashboard');
    }

    /**
     * Decline the invitation.
     */
    public function decline(RespondToBusinessInvitationRequest $request, BusinessInvitation $invitation): RedirectResponse
    {
        $invitation->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation declined.')]);

        return to_route('dashboard');
    }
}
