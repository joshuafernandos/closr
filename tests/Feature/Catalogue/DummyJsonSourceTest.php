<?php

use App\Catalogue\Product;
use App\Catalogue\Sources\DummyJsonSource;
use Illuminate\Support\Facades\Http;

function dummyProduct(array $overrides = []): array
{
    return array_merge([
        'id' => 1,
        'title' => 'Essence Mascara Lash Princess',
        'description' => 'A volumising mascara.',
        'category' => 'beauty',
        'price' => 9.99,
        'rating' => 4.94,
        'brand' => 'Essence',
        'thumbnail' => 'https://cdn.dummyjson.com/mascara.png',
    ], $overrides);
}

function dummySource(): DummyJsonSource
{
    return new DummyJsonSource(url: 'https://dummyjson.com');
}

it('normalises DummyJSON products into Closr products, including category', function () {
    Http::fake([
        '*/products/search*' => Http::response(['products' => [dummyProduct()]]),
    ]);

    $products = dummySource()->search('mascara');

    expect($products)->toHaveCount(1);

    $product = $products->first();

    expect($product)->toBeInstanceOf(Product::class)
        ->and($product->id)->toBe(1)
        ->and($product->title)->toBe('Essence Mascara Lash Princess')
        ->and($product->price)->toBe(9.99)
        ->and($product->rating)->toBe(4.94)
        ->and($product->brand)->toBe('Essence')
        ->and($product->category)->toBe('beauty')
        ->and($product->thumbnail)->toBe('https://cdn.dummyjson.com/mascara.png')
        ->and($product->description)->toBe('A volumising mascara.');
});

it('calls the DummyJSON search endpoint with the search term', function () {
    Http::fake([
        '*/products/search*' => Http::response(['products' => []]),
    ]);

    dummySource()->search('lipstick');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'https://dummyjson.com/products/search')
        && $request['q'] === 'lipstick');
});

it('filters out products above the max price', function () {
    Http::fake([
        '*/products/search*' => Http::response(['products' => [
            dummyProduct(['id' => 1, 'price' => 9.99]),
            dummyProduct(['id' => 2, 'price' => 99.99]),
        ]]),
    ]);

    $products = dummySource()->search('beauty', maxPrice: 50);

    expect($products->pluck('id')->all())->toBe([1]);
});

it('sorts by rating and caps the number of results', function () {
    Http::fake([
        '*/products/search*' => Http::response(['products' => [
            dummyProduct(['id' => 1, 'rating' => 3.0]),
            dummyProduct(['id' => 2, 'rating' => 5.0]),
            dummyProduct(['id' => 3, 'rating' => 4.0]),
        ]]),
    ]);

    $products = dummySource()->search('beauty', limit: 2);

    expect($products->pluck('id')->all())->toBe([2, 3]);
});
