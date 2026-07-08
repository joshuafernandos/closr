<?php

namespace App\Catalogue\Sources;

use App\Catalogue\Contracts\ProductSource;
use App\Catalogue\Product;
use Illuminate\Support\Collection;

/**
 * A no-op origin used when a merchant has not connected a store yet (or the
 * widget key is unknown). The assistant can still greet and qualify; searches
 * simply return nothing so it never recommends phantom products.
 */
class NullProductSource implements ProductSource
{
    /**
     * @return Collection<int, Product>
     */
    public function search(string $query, ?float $maxPrice = null, int $limit = 8): Collection
    {
        return collect();
    }

    /**
     * @return Collection<int, string>
     */
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

    /**
     * @param  array<string, string>  $attributes
     */
    public function variantCartUrl(int $productId, array $attributes, int $quantity = 1): ?string
    {
        return null;
    }
}
