<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConversationMessage>
 */
class ConversationMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'role' => fake()->randomElement(['user', 'assistant']),
            'type' => 'message',
            'content' => fake()->sentence(),
            'step' => null,
            'products' => null,
            'meta' => null,
        ];
    }

    /**
     * Indicate the turn came from the shopper.
     */
    public function fromShopper(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => 'user',
        ]);
    }

    /**
     * Indicate the turn came from the assistant.
     */
    public function fromAssistant(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => 'assistant',
        ]);
    }

    /**
     * Indicate the row is an inline shopper action — a cart addition with the
     * variation they chose — rather than a chat turn.
     *
     * @param  array<string, string>  $variant
     */
    public function addedToCart(array $variant = ['Colour' => 'White', 'Size' => 'M']): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => 'user',
            'type' => 'event',
            'content' => 'Added Metanoia Socks to cart',
            'products' => [
                ['id' => 1, 'title' => 'Metanoia Socks', 'price' => 12.0, 'thumbnail' => ''],
            ],
            'meta' => ['action' => 'added_to_cart', 'variant' => $variant === [] ? null : $variant],
        ]);
    }
}
