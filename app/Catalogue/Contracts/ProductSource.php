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

    /**
     * List the store's most prominent product category names. Powers the
     * widget's quick-pick "preference" chips shown when a shopper opens.
     *
     * @return Collection<int, string>
     */
    public function categories(int $limit = 8): Collection;

    /**
     * Fetch a single product by its origin id. Used to re-hydrate the
     * authoritative details (url, variations, price) for a product the
     * assistant recommended. Returns null when the product can't be found.
     */
    public function find(int $id): ?Product;

    /**
     * Build a storefront URL that adds the product to the shopper's cart and
     * lands them on the cart page with the item already in it. Returns null for
     * origins without a real storefront (e.g. the demo catalogue), so the
     * widget keeps its local bag.
     */
    public function cartUrl(int $productId, int $quantity = 1): ?string;

    /**
     * Build a cart URL for a specific variation of a variable product, choosing
     * the variation that matches the shopper's selected attributes (e.g.
     * ['Size' => 'M', 'Colour' => 'Red']). Returns null when the origin has no
     * real storefront or no variation matches.
     *
     * @param  array<string, string>  $attributes
     */
    public function variantCartUrl(int $productId, array $attributes, int $quantity = 1): ?string;
}
