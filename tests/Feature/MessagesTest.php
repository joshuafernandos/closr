<?php

use App\Enums\BusinessRole;
use App\Models\Business;
use App\Models\Conversation;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

function merchantUser(): User
{
    $user = User::factory()->create();
    $business = Business::factory()->create(['is_personal' => true]);
    $business->members()->attach($user, ['role' => BusinessRole::Owner->value]);
    $user->update(['current_business_id' => $business->id]);

    return $user->refresh();
}

it('redirects guests away from the messages dashboard', function () {
    $this->get(route('messages.index'))->assertRedirect(route('login'));
});

it('lists the current business conversations newest first', function () {
    $user = merchantUser();

    $older = Conversation::factory()->for($user->currentBusiness)->create([
        'last_message_at' => now()->subDay(),
        'title' => 'Older chat',
    ]);
    $newer = Conversation::factory()->for($user->currentBusiness)->create([
        'last_message_at' => now(),
        'title' => 'Newer chat',
    ]);

    $this->actingAs($user)
        ->get(route('messages.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('messages')
            ->where('conversations.total', 2)
            ->where('conversations.data.0.id', $newer->id)
            ->where('conversations.data.1.id', $older->id)
            ->where('selected.id', $newer->id)
        );
});

it('paginates conversations 12 per page', function () {
    $user = merchantUser();

    Conversation::factory()->count(15)->for($user->currentBusiness)->create();

    $this->actingAs($user)
        ->get(route('messages.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('conversations.per_page', 12)
            ->where('conversations.total', 15)
            ->where('conversations.last_page', 2)
            ->count('conversations.data', 12)
        );

    $this->actingAs($user)
        ->get(route('messages.index', ['page' => 2]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->count('conversations.data', 3)
        );
});

it('never shows conversations from another business', function () {
    $user = merchantUser();
    $otherConversation = Conversation::factory()->create();

    $this->actingAs($user)
        ->get(route('messages.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('conversations.total', 0)
            ->where('selected', null)
        );

    $this->actingAs($user)
        ->get(route('messages.show', $otherConversation))
        ->assertNotFound();
});

it('shows a specific conversation transcript', function () {
    $user = merchantUser();
    $conversation = Conversation::factory()->for($user->currentBusiness)->create([
        'title' => 'Gift hunt',
    ]);
    $conversation->messages()->createMany([
        ['role' => 'user', 'content' => 'I need a gift'],
        ['role' => 'assistant', 'content' => 'Sure, who for?', 'step' => 'qualify'],
    ]);

    $this->actingAs($user)
        ->get(route('messages.show', $conversation))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('messages')
            ->where('selected.id', $conversation->id)
            ->where('selected.title', 'Gift hunt')
            ->count('selected.messages', 2)
            ->where('selected.messages.0.content', 'I need a gift')
        );
});
