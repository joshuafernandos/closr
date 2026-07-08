<?php

namespace App\Models;

use Database\Factories\ConversationMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One turn in a {@see Conversation} — either a shopper message or Closr's
 * reply. Assistant turns that recommend products carry the product cards in
 * `products` so the merchant's transcript renders exactly what the shopper saw.
 *
 * @property int $id
 * @property int $conversation_id
 * @property string $role
 * @property string $type
 * @property string $content
 * @property string|null $step
 * @property array<int, array<string, mixed>>|null $products
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Conversation $conversation
 */
#[Fillable(['conversation_id', 'role', 'type', 'content', 'step', 'products', 'meta'])]
class ConversationMessage extends Model
{
    /** @use HasFactory<ConversationMessageFactory> */
    use HasFactory;

    /**
     * Get the conversation this turn belongs to.
     *
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'products' => 'array',
            'meta' => 'array',
        ];
    }
}
