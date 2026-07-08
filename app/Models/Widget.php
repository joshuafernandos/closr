<?php

namespace App\Models;

use App\Enums\WidgetTemplate;
use Database\Factories\WidgetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A merchant's embeddable assistant. Each widget reads from a single catalogue
 * source, carries its own public key for embedding, and renders in one of the
 * available templates with a merchant-chosen accent colour.
 *
 * @property int $id
 * @property int $business_id
 * @property int|null $catalogue_origin_id
 * @property string $name
 * @property string $widget_key
 * @property WidgetTemplate $template
 * @property string $accent_color
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Business $business
 * @property-read CatalogueOrigin|null $catalogueOrigin
 * @property-read Collection<int, Conversation> $conversations
 */
#[Fillable(['business_id', 'catalogue_origin_id', 'name', 'template', 'accent_color'])]
class Widget extends Model
{
    /** @use HasFactory<WidgetFactory> */
    use HasFactory;

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Widget $widget) {
            if (empty($widget->widget_key)) {
                $widget->widget_key = static::generateUniqueWidgetKey();
            }
        });
    }

    /**
     * Generate a public widget key the embedded storefront script uses to
     * identify this widget.
     */
    public static function generateUniqueWidgetKey(): string
    {
        do {
            $key = 'clsr_pub_'.Str::lower(Str::random(32));
        } while (static::where('widget_key', $key)->exists());

        return $key;
    }

    /**
     * Get the business that owns this widget.
     *
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the catalogue source this widget reads from, if any.
     *
     * @return BelongsTo<CatalogueOrigin, $this>
     */
    public function catalogueOrigin(): BelongsTo
    {
        return $this->belongsTo(CatalogueOrigin::class);
    }

    /**
     * Get all shopper conversations captured by this widget.
     *
     * @return HasMany<Conversation, $this>
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'template' => WidgetTemplate::class,
        ];
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'widget_key';
    }
}
