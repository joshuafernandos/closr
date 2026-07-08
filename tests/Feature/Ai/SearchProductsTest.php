<?php

use App\Ai\Tools\SearchProducts;
use App\Catalogue\Contracts\ProductSource;
use App\Catalogue\Product;
use Illuminate\Support\Collection;
use Laravel\Ai\Tools\Request;

/**
 * Build a SearchProducts tool backed by an inline origin returning the given products.
 */
function searchProductsWith(Collection|Closure $products): SearchProducts
{
    $origin = new class($products) implements ProductSource
    {
        public function __construct(private Collection|Closure $products) {}

        public function search(string $query, ?float $maxPrice = null, int $limit = 8): Collection
        {
            return $this->products instanceof Closure
                ? ($this->products)($query, $maxPrice, $limit)
                : $this->products;
        }

        public function categories(int $limit = 8): Collection
        {
            return collect();
        }

        public function find(int $id): ?Product
        {
            return null;
        }

        public function cartUrl(int $productId, int $quantity = 1): ?string
        {
            return null;
        }

        public function variantCartUrl(int $productId, array $attributes, int $quantity = 1): ?string
        {
            return null;
        }
    };

    return new SearchProducts($origin);
}

it('returns the origin products as JSON the assistant can read', function () {
    $tool = searchProductsWith(collect([
        new Product(id: 7, title: 'Trail Runner', price: 89.0, thumbnail: 'https://store.test/shoe.jpg', description: 'Grippy.', rating: 4.6, brand: 'Acme', category: 'shoes'),
    ]));

    $result = (string) $tool->handle(new Request(['query' => 'running shoes']));

    expect(json_decode($result, true))->toBe([[
        'id' => 7,
        'title' => 'Trail Runner',
        'price' => 89,
        'rating' => 4.6,
        'brand' => 'Acme',
        'category' => 'shoes',
        'thumbnail' => 'https://store.test/shoe.jpg',
        'description' => 'Grippy.',
        'url' => null,
        'variations' => [],
    ]]);
});

it('reports when nothing matched so the assistant can adjust', function () {
    $tool = searchProductsWith(collect());

    $result = (string) $tool->handle(new Request(['query' => 'flux capacitor', 'max_price' => 10]));

    expect($result)->toContain('No products matched')->toContain('flux capacitor');
});

it('degrades gracefully when the origin is unreachable', function () {
    $tool = searchProductsWith(fn () => throw new RuntimeException('connection refused'));

    $result = (string) $tool->handle(new Request(['query' => 'jacket']));

    expect($result)->toContain('could not be reached');
});
