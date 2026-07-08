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
            && ! str_contains($request->url(), 'categories')
            && ($request['search'] ?? null) === 'winter coat'
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('ck_test:cs_test'));
    });
});

it('singularises plural search terms so they match singular product names', function () {
    Http::fake([
        '*/wp-json/wc/v3/products*' => Http::response([wooProduct()]),
    ]);

    wooSource()->search('shorts');

    Http::assertSent(fn ($request) => $request['search'] === 'short');
});

it('falls back to category products when the keyword search finds nothing', function () {
    Http::fake([
        '*/wp-json/wc/v3/products/categories*' => Http::response([
            ['id' => 7, 'name' => 'Bottoms'],
            ['id' => 8, 'name' => 'Tops'],
        ]),
        '*/wp-json/wc/v3/products*' => fn ($request) => Http::response(
            isset($request['category']) ? [wooProduct(['id' => 99])] : []
        ),
    ]);

    $products = wooSource()->search('bottoms');

    expect($products->pluck('id')->all())->toBe([99]);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/wp-json/wc/v3/products')
        && ! str_contains($request->url(), 'categories')
        && ($request['category'] ?? null) === '7');
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

it('lists category names, skipping the Uncategorized bucket', function () {
    Http::fake([
        '*/wp-json/wc/v3/products/categories*' => Http::response([
            ['name' => 'Electronics', 'count' => 40],
            ['name' => 'Uncategorized', 'count' => 5],
            ['name' => 'Skincare', 'count' => 12],
        ]),
    ]);

    $categories = wooSource()->categories();

    expect($categories->all())->toBe(['Electronics', 'Skincare']);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/wp-json/wc/v3/products/categories')
        && $request['orderby'] === 'count');
});

it('maps the permalink and variation attributes of a variable product', function () {
    Http::fake([
        '*/wp-json/wc/v3/products*' => Http::response([wooProduct([
            'permalink' => 'https://store.test/product/hoodie/',
            'attributes' => [
                ['name' => 'Size', 'variation' => true, 'options' => ['S', 'M', 'L']],
                ['name' => 'Colour', 'variation' => true, 'options' => ['Red', 'Blue']],
                ['name' => 'Material', 'variation' => false, 'options' => ['Cotton']],
            ],
        ])]),
    ]);

    $product = wooSource()->search('hoodie')->first();

    expect($product->url)->toBe('https://store.test/product/hoodie/')
        ->and($product->variations)->toBe([
            ['name' => 'Size', 'options' => ['S', 'M', 'L']],
            ['name' => 'Colour', 'options' => ['Red', 'Blue']],
        ]);
});

it('leaves variations empty for a simple product', function () {
    Http::fake([
        '*/wp-json/wc/v3/products*' => Http::response([wooProduct()]),
    ]);

    expect(wooSource()->search('jacket')->first()->variations)->toBe([]);
});

it('fetches a single product by id', function () {
    Http::fake([
        '*/wp-json/wc/v3/products/42*' => Http::response(wooProduct(['id' => 42, 'name' => 'Find Me'])),
    ]);

    $product = wooSource()->find(42);

    expect($product)->toBeInstanceOf(Product::class)
        ->and($product->id)->toBe(42)
        ->and($product->title)->toBe('Find Me');
});

it('returns null when a product lookup fails', function () {
    Http::fake([
        '*/wp-json/wc/v3/products/999*' => Http::response([], 404),
    ]);

    expect(wooSource()->find(999))->toBeNull();
});

it('builds a cart URL for the variation matching the chosen attributes', function () {
    Http::fake([
        '*/wp-json/wc/v3/products/10/variations*' => Http::response([
            ['id' => 101, 'attributes' => [['name' => 'Size', 'option' => 'S'], ['name' => 'Colour', 'option' => 'Red']]],
            ['id' => 102, 'attributes' => [['name' => 'Size', 'option' => 'M'], ['name' => 'Colour', 'option' => 'Red']]],
        ]),
    ]);

    $url = wooSource()->variantCartUrl(10, ['Size' => 'M', 'Colour' => 'Red']);

    expect($url)->toContain('https://store.test/cart/?')
        ->and($url)->toContain('add-to-cart=10')
        ->and($url)->toContain('variation_id=102')
        ->and($url)->toContain('attribute_size=M')
        ->and($url)->toContain('attribute_colour=Red');
});

it('returns null when no variation matches the chosen attributes', function () {
    Http::fake([
        '*/wp-json/wc/v3/products/10/variations*' => Http::response([
            ['id' => 101, 'attributes' => [['name' => 'Size', 'option' => 'S']]],
        ]),
    ]);

    expect(wooSource()->variantCartUrl(10, ['Size' => 'XL']))->toBeNull();
});
