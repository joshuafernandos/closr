<?php

namespace App\Models;

use App\Enums\ConversationAction;
use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * A single shopper's chat session with the Closr widget, grouped by the
 * session id the widget keeps in localStorage. Each turn is stored as a
 * {@see ConversationMessage} so the merchant can replay it on the dashboard.
 *
 * @property int $id
 * @property int|null $business_id
 * @property int|null $widget_id
 * @property string $session_id
 * @property string|null $title
 * @property ConversationAction|null $last_action
 * @property Carbon|null $last_message_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Business|null $business
 * @property-read Widget|null $widget
 * @property-read Collection<int, ConversationMessage> $messages
 */
#[Fillable(['business_id', 'widget_id', 'session_id', 'title', 'last_action', 'last_message_at'])]
class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory;

    /**
     * Get the merchant that owns this conversation, if any.
     *
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the widget that captured this conversation, if any.
     *
     * @return BelongsTo<Widget, $this>
     */
    public function widget(): BelongsTo
    {
        return $this->belongsTo(Widget::class);
    }

    /**
     * Get the conversation's turns, oldest first.
     *
     * @return HasMany<ConversationMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class)->oldest();
    }

    /**
     * Get the conversation's most recent turn, for the dashboard list preview.
     *
     * @return HasOne<ConversationMessage, $this>
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(ConversationMessage::class)->latestOfMany();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_action' => ConversationAction::class,
            'last_message_at' => 'datetime',
        ];
    }
}
