<?php

namespace Database\Factories;

use App\Enums\WidgetTemplate;
use App\Models\Business;
use App\Models\CatalogueOrigin;
use App\Models\Widget;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Widget>
 */
class WidgetFactory extends Factory
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
            'catalogue_origin_id' => null,
            'name' => fake()->words(2, true).' widget',
            'template' => WidgetTemplate::Chat,
            'accent_color' => fake()->hexColor(),
        ];
    }

    /**
     * Attach a freshly connected catalogue source owned by the same business.
     */
    public function withSource(): static
    {
        return $this->state(fn (array $attributes): array => [
            'catalogue_origin_id' => CatalogueOrigin::factory()->state([
                'business_id' => $attributes['business_id'] ?? Business::factory(),
            ]),
        ]);
    }

    /**
     * Use the inline component template.
     */
    public function component(): static
    {
        return $this->state(fn (array $attributes): array => [
            'template' => WidgetTemplate::Component,
        ]);
    }
}
