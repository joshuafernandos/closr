<?php

namespace App\Ai\Tools;

use App\Catalogue\Contracts\ProductSource;
use App\Catalogue\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

class SearchProducts implements Tool
{
    public function __construct(private ProductSource $origin) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Search the store catalogue for products matching what the shopper described. '
            .'Call this once you understand what the shopper wants, before recommending anything. '
            .'Returns a JSON list of candidate products with price, rating, brand, category and description.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $query = (string) $request['query'];
        $rawMaxPrice = $request['max_price'] ?? null;
        $maxPrice = $rawMaxPrice !== null ? (float) $rawMaxPrice : null;

        try {
            $products = $this->origin->search($query, $maxPrice);
        } catch (Throwable) {
            return 'The catalogue could not be reached right now. Apologise and ask the shopper to try again.';
        }

        if ($products->isEmpty()) {
            return 'No products matched "'.$query.'"'.($maxPrice !== null ? ' under $'.$maxPrice : '').'. '
                .'Suggest the shopper widen their budget or try a different description.';
        }

        return $products->map(fn (Product $product): array => $product->toArray())->values()->toJson();
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('What the shopper is looking for, e.g. "warm winter jacket" or "smartphone".')->required(),
            'max_price' => $schema->number()->description('Optional maximum price in dollars. Omit if the shopper has no budget limit.'),
        ];
    }
}
