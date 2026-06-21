<?php

namespace App\Ai\Agents;

use App\Ai\Tools\SearchProducts;
use App\Catalogue\Contracts\ProductSource;
use App\Catalogue\Sources\NullProductSource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::Anthropic)]
#[Model('claude-haiku-4-5-20251001')]
#[MaxSteps(5)]
#[MaxTokens(1024)]
class Receptionist implements Agent, Conversational, HasStructuredOutput, HasTools
{
    use Promptable;

    /**
     * The number of most-recent turns to keep in context. The greet → checkout funnel is short
     * by design, so a bounded window keeps prompts small without losing the thread.
     */
    private const HISTORY_WINDOW = 20;

    /**
     * The merchant's catalogue origin the SearchProducts tool searches.
     */
    private ProductSource $origin;

    /**
     * @param  list<array{role: string, content: string}>  $history  Prior turns of the conversation.
     */
    public function __construct(public array $history = [], ?ProductSource $origin = null)
    {
        $this->origin = $origin ?? new NullProductSource;
    }

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
            You are Closr, a friendly in-store shopping assistant. Keep replies to 1–2 short sentences.

            Follow these steps in order:
            1. greet — Welcome the shopper and ask what they're looking for.
            2. qualify — Ask up to 2 specific questions (use case, budget, constraints).
            3. recommend — Call SearchProducts, then recommend 1–2 products only, each with a brief reason. If rejected, suggest alternatives.
            4. checkout — When a product is chosen, confirm it's been added to the bag and complete the order.
            5. done — Conversation finished.

            Structured response:
            - reply: shopper-facing message.
            - step: greet | qualify | recommend | checkout | done.
            - products: only for recommend; include exact id, title, price, thumbnail, category, and reason from SearchProducts. Otherwise [].

            Never recommend products not returned by SearchProducts. Never invent products, prices, or images.
        PROMPT;
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return collect($this->history)
            ->take(-self::HISTORY_WINDOW)
            ->map(fn (array $message): Message => new Message($message['role'], $message['content']))
            ->all();
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            new SearchProducts($this->origin),
        ];
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'reply' => $schema->string()->description('The assistant\'s message to the shopper for this turn.')->required(),
            'step' => $schema->string()->enum(['greet', 'qualify', 'recommend', 'checkout', 'done'])->required(),
            'products' => $schema->array()
                ->description('One or two recommended products. Only populated on the "recommend" step; empty otherwise.')
                ->items(
                    $schema->object(fn (JsonSchema $schema): array => [
                        'id' => $schema->integer()->required(),
                        'title' => $schema->string()->required(),
                        'price' => $schema->number()->required(),
                        'thumbnail' => $schema->string()->required(),
                        'category' => $schema->string()->description('The product category exactly as returned by SearchProducts.'),
                        'reason' => $schema->string()->description('A short reason this product fits the shopper.')->required(),
                    ])
                )
                ->required(),
        ];
    }
}
