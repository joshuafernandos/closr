<?php

namespace App\Catalogue\Sources;

use App\Catalogue\Contracts\ProductSource;
use App\Catalogue\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

/**
 * Searches the public DummyJSON catalogue (`/products/search`). Used as the
 * out-of-the-box demo origin so the chat widget has a live, keyless store to
 * recommend from without a merchant connecting their own.
 */
class DummyJsonSource implements ProductSource
{
    public function __construct(
        private string $url = 'https://dummyjson.com',
        private int $timeout = 15,
    ) {}

    /**
     * Search the DummyJSON catalogue for products matching the shopper's description.
     *
     * @return Collection<int, Product>
     */
    public function search(string $query, ?float $maxPrice = null, int $limit = 8): Collection
    {
        $response = Http::timeout($this->timeout)
            ->get($this->endpoint(), [
                'q' => $query,
                'limit' => 50,
            ]);

        $response->throw();

        return collect($response->json('products') ?? [])
            ->map(fn (array $product): Product => $this->toProduct($product))
            ->when($maxPrice !== null, fn (Collection $products): Collection => $products->filter(
                fn (Product $product): bool => $product->price <= $maxPrice
            ))
            ->sortByDesc(fn (Product $product): float => $product->rating ?? 0.0)
            ->take($limit)
            ->values();
    }

    /**
     * Get the DummyJSON product search endpoint for the configured base URL.
     */
    private function endpoint(): string
    {
        return rtrim($this->url, '/').'/products/search';
    }

    /**
     * Normalise a raw DummyJSON product into a Closr product.
     *
     * @param  array<string, mixed>  $product
     */
    private function toProduct(array $product): Product
    {
        return new Product(
            id: (int) $product['id'],
            title: (string) $product['title'],
            price: (float) ($product['price'] ?? 0),
            thumbnail: (string) ($product['thumbnail'] ?? ''),
            description: trim((string) ($product['description'] ?? '')),
            rating: isset($product['rating']) ? (float) $product['rating'] : null,
            brand: isset($product['brand']) ? (string) $product['brand'] : null,
            category: isset($product['category']) ? (string) $product['category'] : null,
        );
    }
}
