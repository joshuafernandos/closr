<?php

use App\Catalogue\CatalogueManager;
use App\Catalogue\Sources\DummyJsonSource;
use App\Catalogue\Sources\NullProductSource;
use App\Catalogue\Sources\WooCommerceSource;
use App\Models\CatalogueOrigin;
use App\Models\Team;
use Illuminate\Support\Facades\Http;

it('resolves a no-op origin when the merchant has not connected a store', function () {
    $team = Team::factory()->create();

    $source = app(CatalogueManager::class)->forTeam($team);

    expect($source)->toBeInstanceOf(NullProductSource::class)
        ->and($source->search('anything'))->toBeEmpty();
});

it('builds a working origin from the merchant\'s stored config', function () {
    Http::fake(['*/wp-json/wc/v3/products*' => Http::response([])]);

    $team = Team::factory()->create();

    CatalogueOrigin::factory()->for($team)->create([
        'driver' => 'woocommerce',
        'config' => ['url' => 'https://acme.test', 'key' => 'ck_x', 'secret' => 'cs_y'],
    ]);

    $source = app(CatalogueManager::class)->forTeam($team->fresh());

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

it('rejects an unsupported origin driver', function () {
    app(CatalogueManager::class)->make('shopify', []);
})->throws(InvalidArgumentException::class);

it('lets a new platform register its own origin driver', function () {
    $manager = app(CatalogueManager::class);

    $manager->extend('custom', fn (array $config): NullProductSource => new NullProductSource);

    expect($manager->make('custom', []))->toBeInstanceOf(NullProductSource::class);
});

it('gives every team a unique public widget key on creation', function () {
    $a = Team::factory()->create();
    $b = Team::factory()->create();

    expect($a->widget_key)->toStartWith('clsr_pub_')
        ->and($a->widget_key)->not->toBe($b->widget_key);
});
