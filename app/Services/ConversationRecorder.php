<?php

namespace App\Services;

use App\Enums\ConversationAction;
use App\Models\Conversation;
use App\Models\Widget;
use Illuminate\Support\Str;

/**
 * Persists a shopper's widget activity — turns and last actions — into a
 * conversation grouped by their session, so merchants can replay it later.
 */
class ConversationRecorder
{
    /**
     * Find or create the conversation behind a widget key + session id, so each
     * shopper's turns group into one transcript. Returns null when the widget
     * sent no session id (older embeds), leaving the turn unrecorded.
     */
    public function resolveForSession(?string $widgetKey, ?string $session): ?Conversation
    {
        if ($session === null || $session === '') {
            return null;
        }

        $widget = $this->widgetForKey($widgetKey);

        // New conversations start as "Left": the dashboard shows that until the
        // shopper does something better (adds to cart, visits a product page).
        // business_id is kept alongside widget_id so the Messages dashboard can
        // keep scoping conversations by business.
        return Conversation::firstOrCreate(
            ['widget_id' => $widget?->id, 'session_id' => $session],
            ['business_id' => $widget?->business_id, 'last_action' => ConversationAction::Left],
        );
    }

    /**
     * Look up an existing conversation for a widget key + session, without
     * creating one. Returns null when the session is new or unidentified.
     */
    public function findForSession(?string $widgetKey, ?string $session): ?Conversation
    {
        if ($session === null || $session === '') {
            return null;
        }

        $widget = $this->widgetForKey($widgetKey);

        return Conversation::query()
            ->where('widget_id', $widget?->id)
            ->where('session_id', $session)
            ->first();
    }

    /**
     * Append a turn to a conversation and bump its last-message timestamp. The
     * first shopper turn also seeds the conversation title.
     *
     * @param  array<int, array<string, mixed>>  $products
     */
    public function recordTurn(Conversation $conversation, string $role, string $content, ?string $step = null, array $products = []): void
    {
        $conversation->messages()->create([
            'role' => $role,
            'content' => $content,
            'step' => $step,
            'products' => $products === [] ? null : $products,
        ]);

        $attributes = ['last_message_at' => now()];

        if ($role === 'user' && ($conversation->title === null || $conversation->title === '')) {
            $attributes['title'] = Str::limit($content, 80);
        }

        $conversation->update($attributes);
    }

    /**
     * Set the conversation's most recent action for the dashboard status badge.
     */
    public function markAction(Conversation $conversation, ConversationAction $action): void
    {
        $conversation->update(['last_action' => $action]);
    }

    /**
     * Append an inline event row to the transcript — a shopper action like
     * adding a product to the cart, optionally with the variation they picked —
     * so the merchant replays exactly what happened, in order.
     *
     * @param  array<string, mixed>  $product
     * @param  array<string, string>  $variant
     */
    public function recordEvent(Conversation $conversation, ConversationAction $action, array $product, array $variant = []): void
    {
        $conversation->messages()->create([
            'role' => 'user',
            'type' => 'event',
            'content' => $this->eventLabel($action, $product, $variant),
            'products' => [$product],
            'meta' => ['action' => $action->value, 'variant' => $variant === [] ? null : $variant],
        ]);

        $conversation->update(['last_message_at' => now()]);
    }

    /**
     * Build a human-readable fallback label for an event row, used wherever the
     * structured payload isn't rendered (list previews, plain transcripts).
     *
     * @param  array<string, mixed>  $product
     * @param  array<string, string>  $variant
     */
    private function eventLabel(ConversationAction $action, array $product, array $variant): string
    {
        $title = (string) ($product['title'] ?? 'product');
        $options = $variant === [] ? '' : ' ('.implode(' / ', $variant).')';

        return match ($action) {
            ConversationAction::AddedToCart => "Added {$title}{$options} to cart",
            ConversationAction::VisitedProductPage => "Visited {$title} product page",
            ConversationAction::Left => 'Left the conversation',
        };
    }

    /**
     * Resolve the widget behind a widget key, or null for the keyless demo.
     */
    public function widgetForKey(?string $widgetKey): ?Widget
    {
        if ($widgetKey === null || $widgetKey === '') {
            return null;
        }

        return Widget::where('widget_key', $widgetKey)->first();
    }
}
