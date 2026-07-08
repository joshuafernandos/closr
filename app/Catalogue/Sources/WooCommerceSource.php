<?php

namespace App\Catalogue\Sources;

use App\Catalogue\Concerns\MatchesQueryTerms;
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
    use MatchesQueryTerms;

    public function __construct(
        private string $url,
        private string $key,
        private string $secret,
        private int $timeout = 15,
    ) {}

    /**
     * Search the WooCommerce catalogue for products matching the shopper's description.
     *
     * WooCommerce's keyword search only matches product names literally, so shoppers
     * who describe a plural ("shorts" vs a "Short" product) or a category ("bottoms")
     * get no results. The query is singularised before searching, and an empty result
     * falls back to fetching products from any category the shopper named.
     *
     * @return Collection<int, Product>
     */
    public function search(string $query, ?float $maxPrice = null, int $limit = 8): Collection
    {
        $products = $this->fetchProducts(['search' => $this->normaliseSearchTerm($query)]);

        if ($products->isEmpty()) {
            $categoryIds = $this->matchCategoryIds($query);

            if ($categoryIds !== []) {
                $products = $this->fetchProducts(['category' => implode(',', $categoryIds)]);
            }
        }

        $terms = $this->queryTerms($query);

        return $products
            ->when($maxPrice !== null, fn (Collection $products): Collection => $products->filter(
                fn (Product $product): bool => $product->price <= $maxPrice
            ))
            ->sortByDesc(fn (Product $product): array => [
                $this->relevanceScore($product, $terms),
                $product->rating ?? 0.0,
            ])
            ->take($limit)
            ->values();
    }

    /**
     * Fetch published products from the store, merging the given query parameters
     * over the shared defaults.
     *
     * @param  array<string, mixed>  $params
     * @return Collection<int, Product>
     */
    private function fetchProducts(array $params): Collection
    {
        $response = Http::timeout($this->timeout)
            ->withBasicAuth($this->key, $this->secret)
            ->get($this->endpoint(), array_merge([
                'status' => 'publish',
                'orderby' => 'popularity',
                'per_page' => 50,
            ], $params));

        $response->throw();

        return collect($response->json())
            ->map(fn (array $product): Product => $this->toProduct($product));
    }

    /**
     * Resolve the IDs of any store categories the shopper's query names, so a
     * category-style search ("bottoms", "footwear") can fetch their products.
     * Resilient: an unreachable categories endpoint yields no matches.
     *
     * @return array<int, int>
     */
    private function matchCategoryIds(string $query): array
    {
        $queryWords = $this->queryTerms($query);

        if ($queryWords === []) {
            return [];
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->key, $this->secret)
                ->get($this->endpoint('products/categories'), [
                    'hide_empty' => true,
                    'per_page' => 100,
                ]);

            $response->throw();
        } catch (\Throwable) {
            return [];
        }

        return collect($response->json())
            ->filter(fn (array $category): bool => array_intersect(
                $queryWords,
                $this->queryTerms((string) ($category['name'] ?? ''))
            ) !== [])
            ->map(fn (array $category): int => (int) $category['id'])
            ->values()
            ->all();
    }

    /**
     * Normalise a free-text query into a singularised keyword search term.
     */
    private function normaliseSearchTerm(string $query): string
    {
        $words = $this->queryTerms($query);

        return $words === [] ? $query : implode(' ', $words);
    }

    /**
     * List the store's most prominent product category names, busiest first.
     *
     * @return Collection<int, string>
     */
    public function categories(int $limit = 8): Collection
    {
        $response = Http::timeout($this->timeout)
            ->withBasicAuth($this->key, $this->secret)
            ->get($this->endpoint('products/categories'), [
                'orderby' => 'count',
                'order' => 'desc',
                'hide_empty' => true,
                'per_page' => max($limit, 1),
            ]);

        $response->throw();

        return collect($response->json())
            ->map(fn (array $category): string => trim((string) ($category['name'] ?? '')))
            ->filter(fn (string $name): bool => $name !== '' && strtolower($name) !== 'uncategorized')
            ->take($limit)
            ->values();
    }

    /**
     * Fetch a single WooCommerce product by id, normalised into a Closr product.
     * Returns null when the product can't be reached or no longer exists.
     */
    public function find(int $id): ?Product
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->key, $this->secret)
                ->get($this->endpoint("products/{$id}"));

            $response->throw();
        } catch (\Throwable) {
            return null;
        }

        $product = $response->json();

        return is_array($product) && isset($product['id'])
            ? $this->toProduct($product)
            : null;
    }

    /**
     * Build a WooCommerce cart URL that adds the product to the shopper's cart
     * and lands them on the cart page with the item already in it. WooCommerce
     * honours the `add-to-cart` query parameter on any storefront page for
     * simple products, so hitting `/cart/` both adds and displays the item.
     */
    public function cartUrl(int $productId, int $quantity = 1): ?string
    {
        $url = rtrim($this->url, '/').'/cart/?add-to-cart='.$productId;

        return $quantity > 1 ? $url.'&quantity='.$quantity : $url;
    }

    /**
     * Build a cart URL for the variation whose attributes match the shopper's
     * choices. WooCommerce add-to-cart for variable products needs the matching
     * `variation_id` alongside the chosen attribute values, so we look up the
     * product's variations and find the one that fits. Resilient: an unreachable
     * endpoint or no matching variation yields null.
     *
     * @param  array<string, string>  $attributes
     */
    public function variantCartUrl(int $productId, array $attributes, int $quantity = 1): ?string
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->key, $this->secret)
                ->get($this->endpoint("products/{$productId}/variations"), ['per_page' => 100]);

            $response->throw();
        } catch (\Throwable) {
            return null;
        }

        $variation = collect($response->json())
            ->first(fn (array $variation): bool => $this->variationMatches($variation, $attributes));

        if ($variation === null) {
            return null;
        }

        $params = ['add-to-cart' => $productId, 'variation_id' => (int) $variation['id']];

        foreach ($attributes as $name => $value) {
            $params['attribute_'.strtolower(str_replace(' ', '-', $name))] = $value;
        }

        if ($quantity > 1) {
            $params['quantity'] = $quantity;
        }

        return rtrim($this->url, '/').'/cart/?'.http_build_query($params);
    }

    /**
     * Determine whether a WooCommerce variation matches the shopper's chosen
     * attributes. Each variation attribute with a concrete option must equal the
     * shopper's pick for that name (case-insensitive); an empty option means the
     * variation accepts any value ("any size"), so it never disqualifies.
     *
     * @param  array<string, mixed>  $variation
     * @param  array<string, string>  $attributes
     */
    private function variationMatches(array $variation, array $attributes): bool
    {
        foreach ($variation['attributes'] ?? [] as $attribute) {
            $option = (string) ($attribute['option'] ?? '');

            if ($option === '') {
                continue;
            }

            $chosen = $attributes[(string) ($attribute['name'] ?? '')] ?? null;

            if ($chosen === null || strcasecmp($chosen, $option) !== 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get a WooCommerce REST endpoint for the configured store.
     */
    private function endpoint(string $resource = 'products'): string
    {
        return rtrim($this->url, '/')."/wp-json/wc/v3/{$resource}";
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
            url: isset($product['permalink']) ? (string) $product['permalink'] : null,
            variations: $this->toVariations($product['attributes'] ?? []),
        );
    }

    /**
     * Normalise a WooCommerce product's attributes into Closr variation groups,
     * keeping only those that actually drive variations and carry options.
     *
     * @param  array<int, array<string, mixed>>  $attributes
     * @return list<array{name: string, options: list<string>}>
     */
    private function toVariations(array $attributes): array
    {
        return collect($attributes)
            ->filter(fn (array $attribute): bool => ($attribute['variation'] ?? false) === true
                && ! empty($attribute['options']))
            ->map(fn (array $attribute): array => [
                'name' => (string) ($attribute['name'] ?? ''),
                'options' => collect($attribute['options'])
                    ->map(fn (mixed $option): string => (string) $option)
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }
}
