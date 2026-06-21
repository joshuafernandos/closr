<?php

namespace App\Models;

use Database\Factories\CatalogueOriginFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A merchant's connected store. The `driver` selects the platform
 * (woocommerce, ...) and `config` holds that driver's credentials, encrypted
 * at rest, so each driver can store whatever shape it needs.
 *
 * @property int $id
 * @property int $team_id
 * @property string $driver
 * @property array<string, mixed> $config
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 */
#[Fillable(['team_id', 'driver', 'config'])]
class CatalogueOrigin extends Model
{
    /** @use HasFactory<CatalogueOriginFactory> */
    use HasFactory;

    /**
     * Get the team (merchant) that owns this origin.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
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
