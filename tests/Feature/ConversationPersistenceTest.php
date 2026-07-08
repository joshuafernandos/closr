<?php

use App\Ai\Agents\Receptionist;
use App\Ai\Agents\ScopeGuard;
use App\Enums\ConversationAction;
use App\Models\Conversation;
use App\Models\Widget;

beforeEach(function () {
    ScopeGuard::fake(fn (string $prompt) => ['verdict' => 'in_scope']);
});

it('persists a shopper turn and the assistant reply against the session', function () {
    Receptionist::fake(fn (string $prompt) => [
        'reply' => 'Here are some picks.',
        'step' => 'recommend',
        'products' => [
            ['id' => 7, 'title' => 'Laptop', 'price' => 999.0, 'thumbnail' => ''],
        ],
    ]);

    $widget = Widget::factory()->create();

    $this->postJson(route('chat.message'), [
        'key' => $widget->widget_key,
        'session' => 'sess_abc123',
        'message' => 'I need a laptop',
    ])->assertOk();

    $conversation = Conversation::where('session_id', 'sess_abc123')->first();

    expect($conversation)->not->toBeNull()
        ->and($conversation->widget_id)->toBe($widget->id)
        ->and($conversation->business_id)->toBe($widget->business_id)
        ->and($conversation->title)->toBe('I need a laptop')
        ->and($conversation->last_action)->toBe(ConversationAction::Left)
        ->and($conversation->messages)->toHaveCount(2);

    $assistantTurn = $conversation->messages->last();

    expect($assistantTurn->role)->toBe('assistant')
        ->and($assistantTurn->step)->toBe('recommend')
        ->and($assistantTurn->products)->toHaveCount(1);
});

it('does not persist anything when the widget sends no session id', function () {
    Receptionist::fake();

    $this->postJson(route('chat.message'), [
        'message' => 'I need a laptop',
    ])->assertOk();

    expect(Conversation::count())->toBe(0);
});

it('groups multiple turns from the same session into one conversation', function () {
    Receptionist::fake();

    $widget = Widget::factory()->create();

    foreach (['hi', 'something warm'] as $message) {
        $this->postJson(route('chat.message'), [
            'key' => $widget->widget_key,
            'session' => 'sess_same',
            'message' => $message,
        ])->assertOk();
    }

    $conversation = Conversation::where('session_id', 'sess_same')->first();

    expect(Conversation::where('session_id', 'sess_same')->count())->toBe(1)
        ->and($conversation->messages)->toHaveCount(4);
});

it('restores a prior transcript for the session', function () {
    Receptionist::fake(fn (string $prompt) => [
        'reply' => 'Got it.',
        'step' => 'qualify',
        'products' => [],
    ]);

    $widget = Widget::factory()->create();

    $this->postJson(route('chat.message'), [
        'key' => $widget->widget_key,
        'session' => 'sess_restore',
        'message' => 'I need a gift',
    ])->assertOk();

    $this->postJson(route('chat.conversation'), [
        'key' => $widget->widget_key,
        'session' => 'sess_restore',
    ])->assertOk()->assertJson([
        'messages' => [
            ['role' => 'user', 'content' => 'I need a gift'],
            ['role' => 'assistant', 'content' => 'Got it.', 'step' => 'qualify'],
        ],
    ]);
});

it('returns an empty transcript for an unknown session', function () {
    $widget = Widget::factory()->create();

    $this->postJson(route('chat.conversation'), [
        'key' => $widget->widget_key,
        'session' => 'sess_unknown',
    ])->assertOk()->assertExactJson(['messages' => []]);
});

it('records a shopper added-to-cart action on the conversation', function () {
    $widget = Widget::factory()->create();
    Conversation::factory()->for($widget)->create([
        'business_id' => $widget->business_id,
        'session_id' => 'sess_cart',
        'last_action' => ConversationAction::Left,
    ]);

    $this->postJson(route('chat.action'), [
        'key' => $widget->widget_key,
        'session' => 'sess_cart',
        'action' => 'added_to_cart',
    ])->assertOk();

    $conversation = Conversation::where('session_id', 'sess_cart')->first();

    expect($conversation->last_action)->toBe(ConversationAction::AddedToCart);
});

it('records an inline added-to-cart event with the chosen variation', function () {
    $widget = Widget::factory()->create();
    Conversation::factory()->for($widget)->create([
        'business_id' => $widget->business_id,
        'session_id' => 'sess_event',
        'last_action' => ConversationAction::Left,
    ]);

    $this->postJson(route('chat.action'), [
        'key' => $widget->widget_key,
        'session' => 'sess_event',
        'action' => 'added_to_cart',
        'product' => ['id' => 9, 'title' => 'Metanoia Socks', 'price' => 12.5, 'thumbnail' => ''],
        'variant' => ['Colour' => 'White', 'Size' => 'M'],
    ])->assertOk();

    $conversation = Conversation::where('session_id', 'sess_event')->first();
    $event = $conversation->messages->firstWhere('type', 'event');

    expect($conversation->last_action)->toBe(ConversationAction::AddedToCart)
        ->and($event)->not->toBeNull()
        ->and($event->content)->toBe('Added Metanoia Socks (White / M) to cart')
        ->and($event->products)->toHaveCount(1)
        ->and($event->products[0]['title'])->toBe('Metanoia Socks')
        ->and($event->meta['action'])->toBe('added_to_cart')
        ->and($event->meta['variant'])->toBe(['Colour' => 'White', 'Size' => 'M']);
});

it('records a cart action without a product as a badge-only update', function () {
    $widget = Widget::factory()->create();
    Conversation::factory()->for($widget)->create([
        'business_id' => $widget->business_id,
        'session_id' => 'sess_badge',
        'last_action' => ConversationAction::Left,
    ]);

    $this->postJson(route('chat.action'), [
        'key' => $widget->widget_key,
        'session' => 'sess_badge',
        'action' => 'added_to_cart',
    ])->assertOk();

    $conversation = Conversation::where('session_id', 'sess_badge')->first();

    expect($conversation->last_action)->toBe(ConversationAction::AddedToCart)
        ->and($conversation->messages->where('type', 'event'))->toHaveCount(0);
});

it('omits inline events when restoring a widget transcript', function () {
    $widget = Widget::factory()->create();
    $conversation = Conversation::factory()->for($widget)->create([
        'business_id' => $widget->business_id,
        'session_id' => 'sess_restore_event',
    ]);
    $conversation->messages()->create(['role' => 'user', 'content' => 'I need socks']);
    $conversation->messages()->create([
        'role' => 'user',
        'type' => 'event',
        'content' => 'Added Metanoia Socks to cart',
        'products' => [['id' => 1, 'title' => 'Metanoia Socks', 'price' => 12.0, 'thumbnail' => '']],
        'meta' => ['action' => 'added_to_cart', 'variant' => null],
    ]);

    $this->postJson(route('chat.conversation'), [
        'key' => $widget->widget_key,
        'session' => 'sess_restore_event',
    ])->assertOk()->assertExactJson([
        'messages' => [
            ['role' => 'user', 'content' => 'I need socks', 'step' => null, 'products' => []],
        ],
    ]);
});

it('records a shopper visited-product-page action on the conversation', function () {
    $widget = Widget::factory()->create();
    Conversation::factory()->for($widget)->create([
        'business_id' => $widget->business_id,
        'session_id' => 'sess_visit',
        'last_action' => ConversationAction::Left,
    ]);

    $this->postJson(route('chat.action'), [
        'key' => $widget->widget_key,
        'session' => 'sess_visit',
        'action' => 'visited_product_page',
    ])->assertOk();

    $conversation = Conversation::where('session_id', 'sess_visit')->first();

    expect($conversation->last_action)->toBe(ConversationAction::VisitedProductPage);
});

it('defaults a new conversation to the left action', function () {
    Receptionist::fake();

    $widget = Widget::factory()->create();

    $this->postJson(route('chat.message'), [
        'key' => $widget->widget_key,
        'session' => 'sess_default',
        'message' => 'hi',
    ])->assertOk();

    $conversation = Conversation::where('session_id', 'sess_default')->first();

    expect($conversation->last_action)->toBe(ConversationAction::Left);
});

it('rejects an unknown action', function () {
    $widget = Widget::factory()->create();

    $this->postJson(route('chat.action'), [
        'key' => $widget->widget_key,
        'session' => 'sess_x',
        'action' => 'exploded',
    ])->assertStatus(422);
});
