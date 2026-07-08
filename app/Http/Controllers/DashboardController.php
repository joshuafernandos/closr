<?php

namespace App\Http\Controllers;

use App\Models\BusinessInvitation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $email = strtolower($request->user()->email);

        $business = $request->user()->currentBusiness;

        $pendingInvitations = BusinessInvitation::query()
            ->with(['inviter', 'business'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get()
            ->map(fn (BusinessInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'business' => [
                    'name' => $invitation->business->name,
                    'slug' => $invitation->business->slug,
                ],
            ]);

        return Inertia::render('dashboard', [
            'pendingInvitations' => $pendingInvitations,
            'onboarding' => [
                'storeConnected' => $business?->catalogueOrigins()->exists() ?? false,
                'widgetCustomized' => $business?->widgets()->exists() ?? false,
                'widgetPreviewed' => $business?->conversations()->exists() ?? false,
            ],
            'stats' => [
                'conversations' => $business?->conversations()->count() ?? 0,
                'widgets' => $business?->widgets()->count() ?? 0,
                'stores' => $business?->catalogueOrigins()->count() ?? 0,
            ],
        ]);
    }
}
