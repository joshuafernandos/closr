<?php

namespace App\Models;

use Database\Factories\CatalogueOriginFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A merchant's connected store. The `driver` selects the platform
 * (woocommerce, ...) and `config` holds that driver's credentials, encrypted
 * at rest, so each driver can store whatever shape it needs.
 *
 * @property int $id
 * @property int $business_id
 * @property string|null $name
 * @property string $driver
 * @property array<string, mixed> $config
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Business $business
 */
#[Fillable(['business_id', 'name', 'driver', 'config'])]
class CatalogueOrigin extends Model
{
    /** @use HasFactory<CatalogueOriginFactory> */
    use HasFactory;

    /**
     * Get the business (merchant) that owns this origin.
     *
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the widgets reading from this source.
     *
     * @return HasMany<Widget, $this>
     */
    public function widgets(): HasMany
    {
        return $this->hasMany(Widget::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'config' => 'encrypted:array',
        ];
    }
}
