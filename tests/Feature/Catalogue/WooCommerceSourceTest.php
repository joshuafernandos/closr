<?php

use App\Catalogue\Product;
use App\Catalogue\Sources\WooCommerceSource;
use Illuminate\Support\Facades\Http;

function wooProduct(array $overrides = []): array
{
    return array_merge([
        'id' => 1,
        'name' => 'Merino Wool Jacket',
        'price' => '129.00',
        'average_rating' => '4.50',
        'short_description' => '<p>Warm and light.</p>',
        'description' => '<p>Long description.</p>',
        'images' => [['src' => 'https://store.test/jacket.jpg']],
    ], $overrides);
}

function wooSource(): WooCommerceSource
{
    return new WooCommerceSource(
        url: 'https://store.test/',
        key: 'ck_test',
        secret: 'cs_test',
    );
}

it('normalises WooCommerce products into Closr products', function () {
    Http::fake([
        '*/wp-json/wc/v3/products*' => Http::response([wooProduct()]),
    ]);

    $products = wooSource()->search('jacket');

    expect($products)->toHaveCount(1);

    $product = $products->first();

    expect($product)->toBeInstanceOf(Product::class)
        ->and($product->id)->toBe(1)
        ->and($product->title)->toBe('Merino Wool Jacket')
        ->and($product->price)->toBe(129.0)
        ->and($product->rating)->toBe(4.5)
        ->and($product->thumbnail)->toBe('https://store.test/jacket.jpg')
        ->and($product->description)->toBe('Warm and light.');
});

it('calls the WooCommerce REST endpoint with auth and the search term', function () {
    Http::fake([
        '*/wp-json/wc/v3/products*' => Http::response([]),
    ]);

    wooSource()->search('winter coat');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'https://store.test/wp-json/wc/v3/products')
            && $request['search'] === 'winter coat'
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('ck_test:cs_test'));
    });
});

it('filters out products above the max price', function () {
    Http::fake([
        '*/wp-json/wc/v3/products*' => Http::response([
            wooProduct(['id' => 1, 'price' => '50.00']),
            wooProduct(['id' => 2, 'price' => '300.00']),
        ]),
    ]);

    $products = wooSource()->search('jacket', maxPrice: 100);

    expect($products->pluck('id')->all())->toBe([1]);
});

it('sorts by rating and caps the number of results', function () {
    Http::fake([
        '*/wp-json/wc/v3/products*' => Http::response([
            wooProduct(['id' => 1, 'average_rating' => '3.0']),
            wooProduct(['id' => 2, 'average_rating' => '5.0']),
            wooProduct(['id' => 3, 'average_rating' => '4.0']),
        ]),
    ]);

    $products = wooSource()->search('jacket', limit: 2);

    expect($products->pluck('id')->all())->toBe([2, 3]);
});
