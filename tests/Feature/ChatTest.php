<?php

use App\Ai\Agents\Receptionist;
use App\Ai\Agents\ScopeGuard;
use App\Models\CatalogueOrigin;
use App\Models\Widget;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;

/**
 * Fake the gatekeeper with a fixed verdict so tests don't hit the provider.
 */
function fakeScopeGuard(string $verdict = 'in_scope'): void
{
    ScopeGuard::fake(fn (string $prompt) => ['verdict' => $verdict]);
}

/**
 * Create a widget backed by a WooCommerce source with the given store config.
 */
function widgetWithWoo(array $config = ['url' => 'https://acme.test', 'key' => 'ck_x', 'secret' => 'cs_y']): Widget
{
    $origin = CatalogueOrigin::factory()->create([
        'driver' => 'woocommerce',
        'config' => $config,
    ]);

    return Widget::factory()
        ->for($origin->business)
        ->create(['catalogue_origin_id' => $origin->id]);
}

it('renders the widget resolved by its widget key', function () {
    $widget = Widget::factory()->create();

    $this->get(route('widget', $widget->widget_key))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('chat')
            ->where('widgetKey', $widget->widget_key)
            ->where('storeName', $widget->name)
            ->where('template', 'chat')
            ->where('accentColor', $widget->accent_color)
        );
});

it('returns 404 for an unknown widget key', function () {
    $this->get(route('widget', 'clsr_pub_does_not_exist'))->assertNotFound();
});

it('serves a floating launcher loader script for the chat template', function () {
    $widget = Widget::factory()->create();

    $this->get(route('widget.embed', $widget->widget_key))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/javascript')
        ->assertSee(route('widget', $widget), false)
        // The chat template floats a launcher button pinned to the corner.
        ->assertSee('position:fixed', false)
        ->assertSee("createElement('button')", false);
});

it('serves an inline section loader script for the component template', function () {
    $widget = Widget::factory()->component()->create();

    $this->get(route('widget.embed', $widget->widget_key))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/javascript')
        ->assertSee(route('widget', $widget), false)
        // The inline template drops the iframe in the document flow where the
        // loader script sits — no launcher button, no fixed positioning.
        ->assertSee('insertBefore', false)
        ->assertSee('width:100%', false)
        ->assertDontSee("createElement('button')", false)
        ->assertDontSee('position:fixed', false);
});

it('returns 404 for an embed loader with an unknown widget key', function () {
    $this->get(route('widget.embed', 'clsr_pub_does_not_exist'))->assertNotFound();
});

it('renders the sandbox page embedding the first widget', function () {
    $widget = Widget::factory()->create();

    $this->get(route('widget.test'))
        ->assertOk()
        ->assertSee(route('widget.embed', $widget), false)
        ->assertSee($widget->widget_key, false);
});

it('hands the widget the live store categories', function () {
    Http::fake(['*/wp-json/wc/v3/products/categories*' => Http::response([
        ['name' => 'Electronics', 'count' => 40],
        ['name' => 'Skincare', 'count' => 12],
    ])]);

    $widget = widgetWithWoo();

    $this->get(route('widget', $widget->widget_key))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('chat')
            ->where('categories', ['Electronics', 'Skincare'])
        );
});

it('hands the widget an empty category list when no source is connected', function () {
    $widget = Widget::factory()->create();

    $this->get(route('widget', $widget->widget_key))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('chat')
            ->where('categories', [])
        );
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

it('serves a shopper using the source matched by the widget key', function () {
    fakeScopeGuard('in_scope');
    Receptionist::fake();

    $widget = widgetWithWoo();

    $this->postJson(route('chat.message'), [
        'key' => $widget->widget_key,
        'message' => 'I need a winter jacket',
    ])->assertOk()->assertJsonStructure(['reply', 'step', 'products']);

    Receptionist::assertPrompted('I need a winter jacket');
});

it('attaches a WooCommerce cart URL to each recommended product', function () {
    fakeScopeGuard('in_scope');
    Receptionist::fake(fn (string $prompt) => [
        'reply' => 'This one suits you.',
        'step' => 'recommend',
        'products' => [
            ['id' => 42, 'title' => 'Metanoia Classic Short', 'price' => 49.0, 'thumbnail' => '', 'reason' => 'Comfy'],
        ],
    ]);

    // Recommended products are re-hydrated by id, so the detail endpoint is hit.
    Http::fake(['*/wp-json/wc/v3/products/42*' => Http::response(wooProduct(['id' => 42]))]);

    $widget = widgetWithWoo();

    $this->postJson(route('chat.message'), [
        'key' => $widget->widget_key,
        'message' => 'show me bottoms',
    ])->assertOk()
        ->assertJsonPath('products.0.cartUrl', 'https://acme.test/cart/?add-to-cart=42');
});

it('leaves the cart URL null for a store without a real storefront', function () {
    fakeScopeGuard('in_scope');
    Receptionist::fake(fn (string $prompt) => [
        'reply' => 'Here you go.',
        'step' => 'recommend',
        'products' => [
            ['id' => 7, 'title' => 'Demo Item', 'price' => 9.0, 'thumbnail' => '', 'reason' => 'Nice'],
        ],
    ]);

    // No connected source → a no-op origin, so the cart URL is null and no
    // detail lookup is made.
    $widget = Widget::factory()->create();

    $this->postJson(route('chat.message'), [
        'key' => $widget->widget_key,
        'message' => 'show me something',
    ])->assertOk()
        ->assertJsonPath('products.0.cartUrl', null);
});

it('still responds gracefully when the widget key is unknown', function () {
    fakeScopeGuard('in_scope');
    Receptionist::fake();

    $this->postJson(route('chat.message'), [
        'key' => 'clsr_pub_does_not_exist',
        'message' => 'Hello',
    ])->assertOk()->assertJsonStructure(['reply', 'step', 'products']);
});

it('re-hydrates recommended products with their URL and variations', function () {
    fakeScopeGuard('in_scope');
    Receptionist::fake(fn (string $prompt) => [
        'reply' => 'This one suits you.',
        'step' => 'recommend',
        'products' => [
            ['id' => 42, 'title' => 'Hoodie', 'price' => 49.0, 'thumbnail' => '', 'reason' => 'Cosy'],
        ],
    ]);

    Http::fake(['*/wp-json/wc/v3/products/42*' => Http::response(wooProduct([
        'id' => 42,
        'permalink' => 'https://acme.test/product/hoodie/',
        'attributes' => [['name' => 'Size', 'variation' => true, 'options' => ['S', 'M']]],
    ]))]);

    $widget = widgetWithWoo();

    $this->postJson(route('chat.message'), [
        'key' => $widget->widget_key,
        'message' => 'show me a hoodie',
    ])->assertOk()
        ->assertJsonPath('products.0.url', 'https://acme.test/product/hoodie/')
        ->assertJsonPath('products.0.reason', 'Cosy')
        ->assertJsonPath('products.0.variations.0.name', 'Size')
        ->assertJsonPath('products.0.variations.0.options', ['S', 'M']);
});

it('resolves a cart URL for a chosen variation', function () {
    Http::fake(['*/wp-json/wc/v3/products/10/variations*' => Http::response([
        ['id' => 102, 'attributes' => [['name' => 'Size', 'option' => 'M']]],
    ])]);

    $widget = widgetWithWoo();

    $this->postJson(route('chat.variation-cart'), [
        'key' => $widget->widget_key,
        'productId' => 10,
        'attributes' => ['Size' => 'M'],
    ])->assertOk()
        ->assertJsonPath('cartUrl', fn (string $url) => str_contains($url, 'variation_id=102')
            && str_contains($url, 'attribute_size=M'));
});

it('returns a null cart URL for a variation on a store without a real cart', function () {
    $widget = Widget::factory()->create();

    $this->postJson(route('chat.variation-cart'), [
        'key' => $widget->widget_key,
        'productId' => 10,
        'attributes' => ['Size' => 'M'],
    ])->assertOk()->assertJsonPath('cartUrl', null);
});

it('rejects a variation cart request without a product id', function () {
    $this->postJson(route('chat.variation-cart'), [
        'attributes' => ['Size' => 'M'],
    ])->assertStatus(422)->assertJsonValidationErrors('productId');
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

it('browses a category directly without calling the agent', function () {
    fakeScopeGuard();
    Receptionist::fake();

    Http::fake(['*/wp-json/wc/v3/products*' => Http::response([
        ['id' => 42, 'name' => 'Aurora Serum', 'price' => '29.00', 'images' => [['src' => 'https://acme.test/serum.jpg']], 'average_rating' => '4.8', 'categories' => [['name' => 'Skincare']]],
    ])]);

    $widget = widgetWithWoo();

    $this->postJson(route('chat.category'), [
        'key' => $widget->widget_key,
        'category' => 'Skincare',
    ])->assertOk()
        ->assertJsonPath('products.0.id', 42)
        ->assertJsonPath('products.0.title', 'Aurora Serum')
        ->assertJsonPath('products.0.cartUrl', 'https://acme.test/cart/?add-to-cart=42');

    // Browsing a category must never spend a model call.
    ScopeGuard::assertNeverPrompted();
    Receptionist::assertNeverPrompted();
});

it('returns no products when a category browse cannot reach the store', function () {
    Http::fake(['*/wp-json/wc/v3/products*' => Http::response('boom', 500)]);

    $widget = widgetWithWoo();

    $this->postJson(route('chat.category'), [
        'key' => $widget->widget_key,
        'category' => 'Skincare',
    ])->assertOk()->assertJsonPath('products', []);
});

it('requires a category to browse', function () {
    fakeScopeGuard();
    Receptionist::fake();

    $this->postJson(route('chat.category'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('category');

    ScopeGuard::assertNeverPrompted();
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
