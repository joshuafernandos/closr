<?php

namespace Database\Factories;

use App\Enums\ConversationAction;
use App\Models\Business;
use App\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
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
            'session_id' => 'sess_'.Str::lower(Str::random(24)),
            'title' => fake()->sentence(4),
            'last_action' => fake()->randomElement(ConversationAction::cases()),
            'last_message_at' => fake()->dateTimeBetween('-1 week'),
        ];
    }

    /**
     * Indicate the conversation belongs to the keyless demo widget.
     */
    public function keyless(): static
    {
        return $this->state(fn (array $attributes): array => [
            'business_id' => null,
        ]);
    }
}
