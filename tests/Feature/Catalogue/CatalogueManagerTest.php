<?php

use App\Catalogue\CatalogueManager;
use App\Catalogue\Sources\DummyJsonSource;
use App\Catalogue\Sources\NullProductSource;
use App\Catalogue\Sources\WooCommerceSource;
use App\Models\CatalogueOrigin;
use App\Models\Widget;
use Illuminate\Support\Facades\Http;

it('resolves a no-op origin when no source is connected', function () {
    $source = app(CatalogueManager::class)->forOrigin(null);

    expect($source)->toBeInstanceOf(NullProductSource::class)
        ->and($source->search('anything'))->toBeEmpty()
        ->and($source->find(1))->toBeNull()
        ->and($source->cartUrl(1))->toBeNull()
        ->and($source->variantCartUrl(1, ['Size' => 'M']))->toBeNull();
});

it('builds a working origin from a stored source config', function () {
    Http::fake(['*/wp-json/wc/v3/products*' => Http::response([])]);

    $origin = CatalogueOrigin::factory()->create([
        'driver' => 'woocommerce',
        'config' => ['url' => 'https://acme.test', 'key' => 'ck_x', 'secret' => 'cs_y'],
    ]);

    $source = app(CatalogueManager::class)->forOrigin($origin);

    expect($source)->toBeInstanceOf(WooCommerceSource::class);

    $source->search('hat');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'https://acme.test/wp-json/wc/v3/products')
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('ck_x:cs_y')));
});

it('builds a DummyJSON origin and searches its public catalogue', function () {
    Http::fake(['*/products/search*' => Http::response(['products' => []])]);

    $source = app(CatalogueManager::class)->make('dummyjson', ['url' => 'https://dummyjson.com']);

    expect($source)->toBeInstanceOf(DummyJsonSource::class);

    $source->search('phone');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'https://dummyjson.com/products/search'));
});

it('caches a widget\'s resolved categories', function () {
    Http::fake(['*/wp-json/wc/v3/products/categories*' => Http::response([
        ['name' => 'Electronics', 'count' => 40],
        ['name' => 'Gifts', 'count' => 10],
    ])]);

    $origin = CatalogueOrigin::factory()->create([
        'driver' => 'woocommerce',
        'config' => ['url' => 'https://acme.test', 'key' => 'ck_x', 'secret' => 'cs_y'],
    ]);
    $widget = Widget::factory()->for($origin->business)->create([
        'catalogue_origin_id' => $origin->id,
    ]);

    $manager = app(CatalogueManager::class);

    expect($manager->categoriesFor($widget))->toBe(['Electronics', 'Gifts'])
        ->and($manager->categoriesFor($widget))->toBe(['Electronics', 'Gifts']);

    // The second call is served from cache.
    Http::assertSentCount(1);
});

it('returns no categories when the store is unreachable, without caching the failure', function () {
    Http::fake(['*/wp-json/wc/v3/products/categories*' => Http::sequence()
        ->push(['message' => 'down'], 500)
        ->push([['name' => 'Electronics', 'count' => 40]], 200)]);

    $origin = CatalogueOrigin::factory()->create([
        'driver' => 'woocommerce',
        'config' => ['url' => 'https://acme.test', 'key' => 'ck_x', 'secret' => 'cs_y'],
    ]);
    $widget = Widget::factory()->for($origin->business)->create([
        'catalogue_origin_id' => $origin->id,
    ]);

    $manager = app(CatalogueManager::class);

    expect($manager->categoriesFor($widget))->toBe([])
        // The failure was not cached, so a retry picks up the recovered store.
        ->and($manager->categoriesFor($widget))->toBe(['Electronics']);
});

it('returns no categories for a widget without a connected source', function () {
    $widget = Widget::factory()->create();

    expect(app(CatalogueManager::class)->categoriesFor($widget))->toBe([]);
});

it('rejects an unsupported origin driver', function () {
    app(CatalogueManager::class)->make('shopify', []);
})->throws(InvalidArgumentException::class);

it('lets a new platform register its own origin driver', function () {
    $manager = app(CatalogueManager::class);

    $manager->extend('custom', fn (array $config): NullProductSource => new NullProductSource);

    expect($manager->make('custom', []))->toBeInstanceOf(NullProductSource::class);
});

it('gives every widget a unique public widget key on creation', function () {
    $a = Widget::factory()->create();
    $b = Widget::factory()->create();

    expect($a->widget_key)->toStartWith('clsr_pub_')
        ->and($a->widget_key)->not->toBe($b->widget_key);
});
