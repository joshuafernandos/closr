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

it('lists category names from both object and string payloads', function () {
    Http::fake([
        '*/products/categories' => Http::response([
            ['slug' => 'beauty', 'name' => 'Beauty'],
            ['slug' => 'laptops', 'name' => 'Laptops'],
        ]),
    ]);

    expect(dummySource()->categories()->all())->toBe(['Beauty', 'Laptops']);
});

it('sets a product URL and synthesises size variations for apparel', function () {
    Http::fake([
        '*/products/search*' => Http::response(['products' => [
            dummyProduct(['id' => 5, 'category' => 'mens-shirts']),
        ]]),
    ]);

    $product = dummySource()->search('shirt')->first();

    expect($product->url)->toBe('https://dummyjson.com/products/5')
        ->and($product->variations)->toBe([
            ['name' => 'Size', 'options' => ['XS', 'S', 'M', 'L', 'XL']],
        ]);
});

it('synthesises numeric sizes for footwear', function () {
    Http::fake([
        '*/products/search*' => Http::response(['products' => [
            dummyProduct(['id' => 6, 'category' => 'mens-shoes']),
        ]]),
    ]);

    expect(dummySource()->search('shoes')->first()->variations)->toBe([
        ['name' => 'Size', 'options' => ['6', '7', '8', '9', '10', '11']],
    ]);
});

it('leaves non-apparel products without variations', function () {
    Http::fake([
        '*/products/search*' => Http::response(['products' => [dummyProduct(['category' => 'beauty'])]]),
    ]);

    expect(dummySource()->search('mascara')->first()->variations)->toBe([]);
});

it('fetches a single product by id', function () {
    Http::fake([
        '*/products/3' => Http::response(dummyProduct(['id' => 3, 'title' => 'Found'])),
    ]);

    $product = dummySource()->find(3);

    expect($product)->toBeInstanceOf(Product::class)
        ->and($product->id)->toBe(3)
        ->and($product->title)->toBe('Found');
});

it('broadens an over-specific query to its core item when the literal search finds nothing', function () {
    Http::fake([
        '*/products/search?q=white%20socks*' => Http::response(['products' => []]),
        '*/products/search?q=white*' => Http::response(['products' => []]),
        '*/products/search?q=sock*' => Http::response(['products' => [
            dummyProduct(['id' => 1, 'title' => 'Sport Socks', 'category' => 'mens-shoes']),
            dummyProduct(['id' => 2, 'title' => 'White Socks', 'category' => 'mens-shoes']),
        ]]),
    ]);

    $products = dummySource()->search('white socks');

    // Both socks are returned, and the white pair is ranked first by relevance.
    expect($products->pluck('id')->all())->toBe([2, 1]);
});

it('falls back to best-rated products for an open recommendation request', function () {
    Http::fake([
        '*/products/search*' => Http::response(['products' => []]),
        '*/products?*' => Http::response(['products' => [
            dummyProduct(['id' => 1, 'rating' => 4.1]),
            dummyProduct(['id' => 2, 'rating' => 4.9]),
        ]]),
    ]);

    $products = dummySource()->search('give me a recommendation');

    expect($products->pluck('id')->all())->toBe([2, 1]);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/products?')
        && $request['sortBy'] === 'rating');
});

it('returns nothing for a specific item the store does not stock', function () {
    Http::fake([
        '*/products/search*' => Http::response(['products' => []]),
    ]);

    $products = dummySource()->search('flux capacitor');

    expect($products)->toBeEmpty();

    // A named-but-absent item must not trigger the best-rated fallback.
    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/products?')
        && ! str_contains($request->url(), '/search'));
});

it('has no real cart, so variation cart URLs are null', function () {
    expect(dummySource()->variantCartUrl(1, ['Size' => 'M']))->toBeNull();
});
