<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\CatalogueOrigin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CatalogueOrigin>
 */
class CatalogueOriginFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'driver' => 'woocommerce',
            'config' => $this->woocommerceConfig(),
        ];
    }

    /**
     * Indicate the origin is a WooCommerce store.
     */
    public function woocommerce(): static
    {
        return $this->state(fn (array $attributes): array => [
            'driver' => 'woocommerce',
            'config' => $this->woocommerceConfig(),
        ]);
    }

    /**
     * Build a fake WooCommerce credential set.
     *
     * @return array<string, mixed>
     */
    private function woocommerceConfig(): array
    {
        return [
            'url' => 'https://'.fake()->unique()->domainName(),
            'key' => 'ck_'.fake()->sha1(),
            'secret' => 'cs_'.fake()->sha1(),
        ];
    }
}
