<?php

namespace App\Catalogue\Contracts;

use App\Catalogue\Product;
use Illuminate\Support\Collection;

/**
 * An origin store the assistant can search. Implement this contract to plug a
 * new platform (WooCommerce, Shopify, a flat feed, ...) into Closr.
 */
interface ProductSource
{
    /**
     * Search the origin store for products matching the shopper's description.
     *
     * @return Collection<int, Product>
     */
    public function search(string $query, ?float $maxPrice = null, int $limit = 8): Collection;
}
