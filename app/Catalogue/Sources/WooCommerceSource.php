<?php

namespace App\Catalogue\Sources;

use App\Catalogue\Contracts\ProductSource;
use App\Catalogue\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

/**
 * Searches a WooCommerce store via its REST API (`/wp-json/wc/v3/products`),
 * authenticated with a read-only consumer key/secret pair.
 */
class WooCommerceSource implements ProductSource
{
    public function __construct(
        private string $url,
        private string $key,
        private string $secret,
        private int $timeout = 15,
    ) {}

    /**
     * Search the WooCommerce catalogue for products matching the shopper's description.
     *
     * @return Collection<int, Product>
     */
    public function search(string $query, ?float $maxPrice = null, int $limit = 8): Collection
    {
        $response = Http::timeout($this->timeout)
            ->withBasicAuth($this->key, $this->secret)
            ->get($this->endpoint(), [
                'search' => $query,
                'status' => 'publish',
                'orderby' => 'popularity',
                'per_page' => 50,
            ]);

        $response->throw();

        return collect($response->json())
            ->map(fn (array $product): Product => $this->toProduct($product))
            ->when($maxPrice !== null, fn (Collection $products): Collection => $products->filter(
                fn (Product $product): bool => $product->price <= $maxPrice
            ))
            ->sortByDesc(fn (Product $product): float => $product->rating ?? 0.0)
            ->take($limit)
            ->values();
    }

    /**
     * Get the WooCommerce products endpoint for the configured store.
     */
    private function endpoint(): string
    {
        return rtrim($this->url, '/').'/wp-json/wc/v3/products';
    }

    /**
     * Normalise a raw WooCommerce product into a Closr product.
     *
     * @param  array<string, mixed>  $product
     */
    private function toProduct(array $product): Product
    {
        $body = $product['short_description'] ?? $product['description'] ?? '';

        return new Product(
            id: (int) $product['id'],
            title: (string) $product['name'],
            price: (float) ($product['price'] ?? 0),
            thumbnail: (string) ($product['images'][0]['src'] ?? ''),
            description: trim(strip_tags((string) $body)),
            rating: isset($product['average_rating']) ? (float) $product['average_rating'] : null,
            category: isset($product['categories'][0]['name']) ? (string) $product['categories'][0]['name'] : null,
        );
    }
}
