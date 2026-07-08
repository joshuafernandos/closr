<?php

namespace App\Catalogue\Concerns;

use App\Catalogue\Product;

/**
 * Shared loose-matching helpers for catalogue sources: tokenising a shopper's
 * free-text query into singularised terms, and scoring how well a product
 * matches those terms. Lets sources broaden over-specific searches ("white
 * socks" → "socks") and rank the results by relevance rather than rating alone.
 */
trait MatchesQueryTerms
{
    /**
     * Filler words a shopper may wrap a request in ("give me a recommendation",
     * "show me the best thing"). They carry no product meaning, so they are
     * dropped before deciding what to search for. When a query is *nothing but*
     * these, the source treats it as an open "recommend me your best" request.
     *
     * @var list<string>
     */
    private array $queryStopWords = [
        'a', 'an', 'the', 'me', 'my', 'i', 'you', 'your', 'we', 'us', 'it', 'is', 'are',
        'give', 'show', 'find', 'get', 'want', 'need', 'looking', 'look', 'for', 'with',
        'some', 'any', 'something', 'anything', 'thing', 'please', 'recommend', 'recommendation',
        'suggest', 'suggestion', 'best', 'good', 'great', 'top', 'pick', 'choose', 'product',
        'products', 'item', 'items', 'option', 'options', 'store', 'shop', 'from', 'in', 'of',
        'to', 'and', 'or', 'do', 'have', 'has', 'can', 'could', 'would', 'about', 'whats', 'what',
    ];

    /**
     * Split free text into lowercased, singularised, de-duplicated terms for
     * loose matching.
     *
     * @return list<string>
     */
    protected function queryTerms(string $text): array
    {
        return array_values(collect(preg_split('/[^a-z0-9]+/i', strtolower($text)) ?: [])
            ->filter(fn (string $word): bool => $word !== '')
            ->map(fn (string $word): string => $this->singulariseTerm($word))
            ->unique()
            ->all());
    }

    /**
     * The meaningful product terms in a query — its terms with filler words
     * removed. Empty when the shopper only asked for a generic recommendation.
     *
     * @return list<string>
     */
    protected function meaningfulTerms(string $text): array
    {
        return array_values(collect($this->queryTerms($text))
            ->reject(fn (string $term): bool => in_array($term, $this->queryStopWords, true))
            ->all());
    }

    /**
     * Strip a trailing plural "s" so "shorts" matches a "Short" product or a
     * "Bottoms" category. Words ending in "ss" (e.g. "dress") are left alone.
     */
    protected function singulariseTerm(string $word): string
    {
        if (strlen($word) > 3 && str_ends_with($word, 's') && ! str_ends_with($word, 'ss')) {
            return substr($word, 0, -1);
        }

        return $word;
    }

    /**
     * Score how well a product matches the query terms: the number of distinct
     * terms that appear anywhere in its title, description, category or brand.
     * Lets "white socks" rank white socks above plain ones even when the search
     * had to broaden to "socks" to find anything at all.
     *
     * @param  list<string>  $terms
     */
    protected function relevanceScore(Product $product, array $terms): int
    {
        if ($terms === []) {
            return 0;
        }

        $haystack = $this->queryTerms(implode(' ', array_filter([
            $product->title,
            $product->description,
            $product->category,
            $product->brand,
        ])));

        return count(array_intersect($terms, $haystack));
    }
}
