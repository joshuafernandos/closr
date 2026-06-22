<?php

namespace App\Http\Controllers\Businesses;

use App\Actions\Businesses\CreateBusiness;
use App\Enums\BusinessRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Businesses\DeleteBusinessRequest;
use App\Http\Requests\Businesses\SaveBusinessRequest;
use App\Models\Membership;
use App\Models\Business;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BusinessController extends Controller
{
    /**
     * Display a listing of the user's businesses.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('businesses/index', [
            'businesses' => $user->toUserBusinesses(includeCurrent: true),
        ]);
    }

    /**
     * Store a newly created business.
     */
    public function store(SaveBusinessRequest $request, CreateBusiness $createBusiness): RedirectResponse
    {
        $business = $createBusiness->handle($request->user(), $request->validated('name'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Business created.')]);

        return to_route('businesses.edit', ['business' => $business->slug]);
    }

    /**
     * Show the business edit page.
     */
    public function edit(Request $request, Business $business): Response
    {
        $user = $request->user();

        return Inertia::render('businesses/edit', [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'slug' => $business->slug,
                'isPersonal' => $business->is_personal,
            ],
            'members' => $business->members()->get()->map(function (User $member) {
                /** @var Membership $membership */
                $membership = $member->getRelation('pivot');

                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'avatar' => $member->avatar ?? null,
                    'role' => $membership->role->value,
                    'role_label' => $membership->role->label(),
                ];
            }),
            'invitations' => $business->invitations()
                ->whereNull('accepted_at')
                ->get()
                ->map(fn ($invitation) => [
                    'code' => $invitation->code,
                    'email' => $invitation->email,
                    'role' => $invitation->role->value,
                    'role_label' => $invitation->role->label(),
                    'created_at' => $invitation->created_at->toISOString(),
                ]),
            'permissions' => $user->toBusinessPermissions($business),
            'availableRoles' => BusinessRole::assignable(),
        ]);
    }

    /**
     * Update the specified business.
     */
    public function update(SaveBusinessRequest $request, Business $business): RedirectResponse
    {
        Gate::authorize('update', $business);

        $business = DB::transaction(function () use ($request, $business) {
            $business = Business::whereKey($business->id)->lockForUpdate()->firstOrFail();

            $business->update(['name' => $request->validated('name')]);

            return $business;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Business updated.')]);

        return to_route('businesses.edit', ['business' => $business->slug]);
    }

    /**
     * Switch the user's current business.
     */
    public function switch(Request $request, Business $business): RedirectResponse
    {
        abort_unless($request->user()->belongsToBusiness($business), 403);

        $request->user()->switchBusiness($business);

        return back();
    }

    /**
     * Leave the specified business.
     */
    public function leave(Request $request, Business $business): RedirectResponse
    {
        Gate::authorize('leave', $business);

        $user = $request->user();

        $fallbackBusiness = $user->isCurrentBusiness($business)
            ? $user->fallbackBusiness($business)
            : null;

        $business->memberships()
            ->where('user_id', $user->id)
            ->delete();

        if ($fallbackBusiness) {
            $user->switchBusiness($fallbackBusiness);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('You left the business ":name"', ['name' => $business->name])]);

        return to_route('businesses.index');
    }

    /**
     * Delete the specified business.
     */
    public function destroy(DeleteBusinessRequest $request, Business $business): RedirectResponse
    {
        $user = $request->user();
        $fallbackBusiness = $user->isCurrentBusiness($business)
            ? $user->fallbackBusiness($business)
            : null;

        DB::transaction(function () use ($user, $business) {
            User::where('current_business_id', $business->id)
                ->where('id', '!=', $user->id)
                ->each(fn (User $affectedUser) => $affectedUser->switchBusiness($affectedUser->personalBusiness()));

            $business->invitations()->delete();
            $business->memberships()->delete();
            $business->delete();
        });

        if ($fallbackBusiness) {
            $user->switchBusiness($fallbackBusiness);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Business deleted.')]);

        return to_route('businesses.index');
    }
}
