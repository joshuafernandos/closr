<?php

use App\Ai\Agents\Receptionist;
use App\Ai\Agents\ScopeGuard;
use App\Models\CatalogueOrigin;
use App\Models\Team;
use Inertia\Testing\AssertableInertia;

/**
 * Fake the gatekeeper with a fixed verdict so tests don't hit the provider.
 */
function fakeScopeGuard(string $verdict = 'in_scope'): void
{
    ScopeGuard::fake(fn (string $prompt) => ['verdict' => $verdict]);
}

it('renders the widget for a merchant resolved by their widget key', function () {
    $team = Team::factory()->create();

    $this->get(route('widget', $team->widget_key))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('chat')
            ->where('widgetKey', $team->widget_key)
            ->where('storeName', $team->name)
        );
});

it('returns 404 for an unknown widget key', function () {
    $this->get(route('widget', 'clsr_pub_does_not_exist'))->assertNotFound();
});

it('returns a structured reply for a shopper message', function () {
    fakeScopeGuard('in_scope');
    Receptionist::fake();

    $response = $this->postJson(route('chat.message'), [
        'message' => 'I need a laptop for design work',
        'history' => [
            ['role' => 'user', 'content' => 'The shopper just opened the chat.'],
            ['role' => 'assistant', 'content' => 'Hi! What brings you in today?'],
        ],
    ]);

    $response->assertOk()
        ->assertJsonStructure(['reply', 'step', 'products']);

    Receptionist::assertPrompted('I need a laptop for design work');
});

it('works without any prior history', function () {
    fakeScopeGuard('in_scope');
    Receptionist::fake();

    $this->postJson(route('chat.message'), [
        'message' => 'The shopper just opened the chat.',
    ])->assertOk()->assertJsonStructure(['reply', 'step', 'products']);
});

it('serves a shopper using the merchant matched by the widget key', function () {
    fakeScopeGuard('in_scope');
    Receptionist::fake();

    $team = Team::factory()->create();
    CatalogueOrigin::factory()->for($team)->create();

    $this->postJson(route('chat.message'), [
        'key' => $team->widget_key,
        'message' => 'I need a winter jacket',
    ])->assertOk()->assertJsonStructure(['reply', 'step', 'products']);

    Receptionist::assertPrompted('I need a winter jacket');
});

it('still responds gracefully when the widget key is unknown', function () {
    fakeScopeGuard('in_scope');
    Receptionist::fake();

    $this->postJson(route('chat.message'), [
        'key' => 'clsr_pub_does_not_exist',
        'message' => 'Hello',
    ])->assertOk()->assertJsonStructure(['reply', 'step', 'products']);
});

it('filters out messages that are not about products or the store', function () {
    fakeScopeGuard('out_of_scope');
    Receptionist::fake();

    $this->postJson(route('chat.message'), [
        'message' => 'What is the capital of France?',
    ])->assertOk()
        ->assertJson(['step' => 'out_of_scope', 'products' => []])
        ->assertJsonPath('reply', fn (string $reply) => str_contains($reply, 'find and buy the right product'));

    // The off-topic turn must never reach the Receptionist or the catalogue.
    Receptionist::assertNeverPrompted();
});

it('requires a message', function () {
    fakeScopeGuard();
    Receptionist::fake();

    $this->postJson(route('chat.message'), [
        'history' => [],
    ])->assertStatus(422)->assertJsonValidationErrorFor('message');

    ScopeGuard::assertNeverPrompted();
    Receptionist::assertNeverPrompted();
});

it('rejects an oversized history to keep prompt context bounded', function () {
    fakeScopeGuard();
    Receptionist::fake();

    $history = array_fill(0, 101, ['role' => 'user', 'content' => 'hi']);

    $this->postJson(route('chat.message'), [
        'message' => 'Hello',
        'history' => $history,
    ])->assertStatus(422)->assertJsonValidationErrorFor('history');

    ScopeGuard::assertNeverPrompted();
    Receptionist::assertNeverPrompted();
});

it('rejects history entries with an invalid role', function () {
    fakeScopeGuard();
    Receptionist::fake();

    $this->postJson(route('chat.message'), [
        'message' => 'Hello',
        'history' => [
            ['role' => 'system', 'content' => 'nope'],
        ],
    ])->assertStatus(422)->assertJsonValidationErrorFor('history.0.role');
});
