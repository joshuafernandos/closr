<?php

namespace App\Catalogue;

use Illuminate\Contracts\Support\Arrayable;

/**
 * A single product, normalised from whatever origin store it came from so the
 * assistant sees the same shape regardless of platform (WooCommerce, etc).
 *
 * @implements Arrayable<string, mixed>
 */
class Product implements Arrayable
{
    public function __construct(
        public int $id,
        public string $title,
        public float $price,
        public string $thumbnail,
        public string $description,
        public ?float $rating = null,
        public ?string $brand = null,
        public ?string $category = null,
    ) {}

    /**
     * Get the array representation handed to the assistant as a search result.
     *
     * @return array{id: int, title: string, price: float, rating: float|null, brand: string|null, category: string|null, thumbnail: string, description: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'price' => $this->price,
            'rating' => $this->rating,
            'brand' => $this->brand,
            'category' => $this->category,
            'thumbnail' => $this->thumbnail,
            'description' => $this->description,
        ];
    }
}
