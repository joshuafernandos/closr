<?php

namespace App\Catalogue\Sources;

use App\Catalogue\Concerns\MatchesQueryTerms;
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
    use MatchesQueryTerms;

    public function __construct(
        private string $url = 'https://dummyjson.com',
        private int $timeout = 15,
    ) {}

    /**
     * Search the DummyJSON catalogue for products matching the shopper's description.
     *
     * DummyJSON's keyword search matches the whole phrase, so a descriptive query
     * ("white socks") finds nothing even when the store stocks the item ("socks").
     * The search broadens progressively — dropping leading qualifiers until a term
     * lands results — and an open "recommend me something" request falls back to the
     * store's best-rated products. Results are then ranked by how closely they match
     * the shopper's full description so the qualifiers ("white") still steer ordering.
     *
     * @return Collection<int, Product>
     */
    public function search(string $query, ?float $maxPrice = null, int $limit = 8): Collection
    {
        $products = $this->resolveProducts($query);

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
     * Find candidate products for a query: the literal search first, then
     * progressively broader searches, and finally the store's best-rated
     * products when the shopper only asked for a general recommendation.
     *
     * @return Collection<int, Product>
     */
    private function resolveProducts(string $query): Collection
    {
        $tried = [];

        foreach ($this->searchCandidates($query) as $candidate) {
            if (in_array($candidate, $tried, true)) {
                continue;
            }

            $tried[] = $candidate;
            $products = $this->fetchSearch($candidate);

            if ($products->isNotEmpty()) {
                return $products;
            }
        }

        return $this->meaningfulTerms($query) === []
            ? $this->fetchPopular()
            : collect();
    }

    /**
     * The ordered list of search terms to try for a query: the shopper's exact
     * words first, then the meaningful terms with leading qualifiers peeled off
     * one at a time ("white sock" → "sock") so the head noun gets its own search.
     *
     * @return list<string>
     */
    private function searchCandidates(string $query): array
    {
        $candidates = [trim($query)];
        $terms = $this->meaningfulTerms($query);

        while ($terms !== []) {
            $candidates[] = implode(' ', $terms);
            array_shift($terms);
        }

        return array_values(collect($candidates)
            ->filter(fn (string $candidate): bool => $candidate !== '')
            ->unique()
            ->all());
    }

    /**
     * Run a single DummyJSON keyword search and normalise the results.
     *
     * @return Collection<int, Product>
     */
    private function fetchSearch(string $query): Collection
    {
        $response = Http::timeout($this->timeout)
            ->get($this->endpoint(), [
                'q' => $query,
                'limit' => 50,
            ]);

        $response->throw();

        return collect($response->json('products') ?? [])
            ->map(fn (array $product): Product => $this->toProduct($product));
    }

    /**
     * Fetch the store's best-rated products, for when the shopper asks for a
     * recommendation without naming anything. Resilient: an unreachable endpoint
     * yields nothing rather than failing the turn.
     *
     * @return Collection<int, Product>
     */
    private function fetchPopular(): Collection
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get(rtrim($this->url, '/').'/products', [
                    'sortBy' => 'rating',
                    'order' => 'desc',
                    'limit' => 50,
                ]);

            $response->throw();
        } catch (\Throwable) {
            return collect();
        }

        return collect($response->json('products') ?? [])
            ->map(fn (array $product): Product => $this->toProduct($product));
    }

    /**
     * List the DummyJSON catalogue's product category names.
     *
     * @return Collection<int, string>
     */
    public function categories(int $limit = 8): Collection
    {
        $response = Http::timeout($this->timeout)
            ->get(rtrim($this->url, '/').'/products/categories');

        $response->throw();

        return collect($response->json())
            ->map(fn (mixed $category): string => is_array($category)
                ? (string) ($category['name'] ?? $category['slug'] ?? '')
                : (string) $category)
            ->map(fn (string $name): string => trim($name))
            ->filter(fn (string $name): bool => $name !== '')
            ->take($limit)
            ->values();
    }

    /**
     * Fetch a single DummyJSON product by id, normalised into a Closr product.
     * Returns null when the product can't be reached or doesn't exist.
     */
    public function find(int $id): ?Product
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get(rtrim($this->url, '/')."/products/{$id}");

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
     * The demo catalogue has no real storefront, so there is no cart to add to
     * — the widget keeps its local, demo-only bag instead.
     */
    public function cartUrl(int $productId, int $quantity = 1): ?string
    {
        return null;
    }

    /**
     * The demo catalogue has no real cart, so a chosen variation is just added
     * to the widget's local bag rather than a storefront cart.
     *
     * @param  array<string, string>  $attributes
     */
    public function variantCartUrl(int $productId, array $attributes, int $quantity = 1): ?string
    {
        return null;
    }

    /**
     * Get the DummyJSON product search endpoint for the configured base URL.
     */
    private function endpoint(): string
    {
        return rtrim($this->url, '/').'/products/search';
    }

    /**
     * Normalise a raw DummyJSON product into a Closr product. DummyJSON has no
     * variations of its own, so for the demo we synthesise size options for
     * apparel and footwear categories from the product's category name.
     *
     * @param  array<string, mixed>  $product
     */
    private function toProduct(array $product): Product
    {
        $category = isset($product['category']) ? (string) $product['category'] : null;

        return new Product(
            id: (int) $product['id'],
            title: (string) $product['title'],
            price: (float) ($product['price'] ?? 0),
            thumbnail: (string) ($product['thumbnail'] ?? ''),
            description: trim((string) ($product['description'] ?? '')),
            rating: isset($product['rating']) ? (float) $product['rating'] : null,
            brand: isset($product['brand']) ? (string) $product['brand'] : null,
            category: $category,
            url: rtrim($this->url, '/').'/products/'.(int) $product['id'],
            variations: $this->synthesiseVariations($category),
        );
    }

    /**
     * Fabricate demo variation options from a product's category so the widget's
     * variation flow is demoable without a real variable-product catalogue.
     * Footwear gets numeric shoe sizes, apparel gets letter sizes, everything
     * else stays a simple product.
     *
     * @return list<array{name: string, options: list<string>}>
     */
    private function synthesiseVariations(?string $category): array
    {
        if ($category === null) {
            return [];
        }

        $category = strtolower($category);
        $apparelHints = ['shirt', 'top', 'dress', 'jacket', 'hoodie', 'sweater', 'tshirt', 't-shirt', 'clothing', 'blouse', 'coat'];
        $footwearHints = ['shoe', 'sneaker', 'boot', 'footwear', 'sandal'];

        foreach ($footwearHints as $hint) {
            if (str_contains($category, $hint)) {
                return [['name' => 'Size', 'options' => ['6', '7', '8', '9', '10', '11']]];
            }
        }

        foreach ($apparelHints as $hint) {
            if (str_contains($category, $hint)) {
                return [['name' => 'Size', 'options' => ['XS', 'S', 'M', 'L', 'XL']]];
            }
        }

        return [];
    }
}
