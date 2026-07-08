<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Shapes conversations for the surfaces that read them: the merchant dashboard
 * list and detail pane, and the widget's session-restore payload.
 */
class ConversationPresenter
{
    /**
     * Number of conversations shown per page in the dashboard list.
     */
    private const PER_PAGE = 12;

    /**
     * Build the paginated conversation list for the sidebar, newest activity
     * first, shaped for the dashboard.
     *
     * @return LengthAwarePaginator<int, mixed>
     */
    public function paginate(Business $business): LengthAwarePaginator
    {
        return $business->conversations()
            ->with('latestMessage')
            ->withCount('messages')
            ->latest('last_message_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (Conversation $conversation): array => [
                'id' => $conversation->id,
                'title' => $conversation->title ?? 'New conversation',
                'preview' => $conversation->latestMessage?->content,
                'lastAction' => $conversation->last_action?->value,
                'lastActionLabel' => $conversation->last_action?->label(),
                'messageCount' => $conversation->messages_count,
                'lastMessageAt' => $conversation->last_message_at?->toIso8601String(),
            ]);
    }

    /**
     * Shape a conversation and its full transcript for the detail pane.
     *
     * @return array<string, mixed>|null
     */
    public function selected(?Conversation $conversation): ?array
    {
        if ($conversation === null) {
            return null;
        }

        return [
            'id' => $conversation->id,
            'title' => $conversation->title ?? 'New conversation',
            'lastAction' => $conversation->last_action?->value,
            'lastActionLabel' => $conversation->last_action?->label(),
            'lastMessageAt' => $conversation->last_message_at?->toIso8601String(),
            'messages' => $conversation->messages
                ->map(fn (ConversationMessage $message): array => [
                    'id' => $message->id,
                    'role' => $message->role,
                    'type' => $message->type,
                    'content' => $message->content,
                    'step' => $message->step,
                    'products' => $message->products ?? [],
                    'meta' => $message->meta,
                    'createdAt' => $message->created_at?->toIso8601String(),
                ])
                ->all(),
        ];
    }

    /**
     * Shape a conversation's turns for the widget to rehydrate a returning
     * shopper's session.
     *
     * @return array<int, array<string, mixed>>
     */
    public function transcript(Conversation $conversation): array
    {
        return $conversation->messages
            // Event rows (cart additions, variation picks) are a merchant-only
            // replay aid; the widget renders its own confirmations, so it
            // restores chat turns alone.
            ->where('type', 'message')
            ->map(fn (ConversationMessage $message): array => [
                'role' => $message->role,
                'content' => $message->content,
                'step' => $message->step,
                'products' => $message->products ?? [],
            ])
            ->values()
            ->all();
    }
}
