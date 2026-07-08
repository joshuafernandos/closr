<?php

namespace App\Models;

use App\Concerns\GeneratesUniqueBusinessSlugs;
use App\Enums\BusinessRole;
use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $widget_key
 * @property bool $is_personal
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, CatalogueOrigin> $catalogueOrigins
 * @property-read Collection<int, Widget> $widgets
 * @property-read Collection<int, Conversation> $conversations
 * @property-read Collection<int, BusinessInvitation> $invitations
 * @property-read Collection<int, Membership> $memberships
 * @property-read Collection<int, User> $members
 */
#[Fillable(['name', 'slug', 'is_personal'])]
class Business extends Model
{
    /** @use HasFactory<BusinessFactory> */
    use GeneratesUniqueBusinessSlugs, HasFactory, SoftDeletes;

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Business $business) {
            if (empty($business->slug)) {
                $business->slug = static::generateUniqueBusinessSlug($business->name);
            }

            if (empty($business->widget_key)) {
                $business->widget_key = static::generateUniqueWidgetKey();
            }
        });

        static::updating(function (Business $business) {
            if ($business->isDirty('name')) {
                $business->slug = static::generateUniqueBusinessSlug($business->name, $business->id);
            }
        });
    }

    /**
     * Get the business owner.
     */
    public function owner(): ?Model
    {
        return $this->members()
            ->wherePivot('role', BusinessRole::Owner->value)
            ->first();
    }

    /**
     * Generate a public widget key the embedded storefront script uses to identify this business.
     */
    public static function generateUniqueWidgetKey(): string
    {
        do {
            $key = 'clsr_pub_'.Str::lower(Str::random(32));
        } while (static::where('widget_key', $key)->exists());

        return $key;
    }

    /**
     * Get the merchant's connected catalogue origins (their stores).
     *
     * @return HasMany<CatalogueOrigin, $this>
     */
    public function catalogueOrigins(): HasMany
    {
        return $this->hasMany(CatalogueOrigin::class);
    }

    /**
     * Get the merchant's embeddable widgets.
     *
     * @return HasMany<Widget, $this>
     */
    public function widgets(): HasMany
    {
        return $this->hasMany(Widget::class);
    }

    /**
     * Get all shopper conversations captured by this business's widget.
     *
     * @return HasMany<Conversation, $this>
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Get all members of this business.
     *
     * @return BelongsToMany<User, $this, Membership, 'pivot'>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'business_user', 'business_id', 'user_id')
            ->using(Membership::class)
            ->withPivot(['role'])
            ->withTimestamps();
    }

    /**
     * Get all memberships for this business.
     *
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * Get all invitations for this business.
     *
     * @return HasMany<BusinessInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(BusinessInvitation::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_personal' => 'boolean',
        ];
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
