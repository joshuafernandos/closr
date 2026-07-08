<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\ConversationPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MessageController extends Controller
{
    public function __construct(private ConversationPresenter $presenter) {}

    /**
     * Show the merchant's shopper conversations, with the most recent one open.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Conversation::class);

        $business = $request->user()->currentBusiness;

        $selected = $business->conversations()
            ->with('messages')
            ->latest('last_message_at')
            ->first();

        return Inertia::render('messages', [
            'conversations' => $this->presenter->paginate($business),
            'selected' => $this->presenter->selected($selected),
        ]);
    }

    /**
     * Show a specific conversation alongside the paginated list.
     */
    public function show(Request $request, Conversation $conversation): Response
    {
        Gate::authorize('viewAny', Conversation::class);
        Gate::authorize('view', $conversation);

        $business = $request->user()->currentBusiness;
        $conversation->load('messages');

        return Inertia::render('messages', [
            'conversations' => $this->presenter->paginate($business),
            'selected' => $this->presenter->selected($conversation),
        ]);
    }
}
