<?php

namespace App\Concerns;

use App\Data\BusinessPermissions;
use App\Data\UserBusiness;
use App\Enums\BusinessPermission;
use App\Enums\BusinessRole;
use App\Models\Business;
use App\Models\Membership;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;

trait HasBusinesses
{
    /**
     * Get all of the businesses the user belongs to.
     *
     * @return BelongsToMany<Business, $this>
     */
    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class, 'business_user', 'user_id', 'business_id')
            ->withPivot(['role'])
            ->withTimestamps();
    }

    /**
     * Get all of the businesses the user owns.
     *
     * @return HasManyThrough<Business, Membership, $this>
     */
    public function ownedBusinesses(): HasManyThrough
    {
        return $this->hasManyThrough(
            Business::class,
            Membership::class,
            'user_id',
            'id',
            'id',
            'business_id',
        )->where('business_user.role', BusinessRole::Owner->value);
    }

    /**
     * Get all of the memberships for the user.
     *
     * @return HasMany<Membership, $this>
     */
    public function businessMemberships(): HasMany
    {
        return $this->hasMany(Membership::class, 'user_id');
    }

    /**
     * Get the user's current business.
     *
     * @return BelongsTo<Business, $this>
     */
    public function currentBusiness(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'current_business_id');
    }

    /**
     * Get the user's personal business.
     */
    public function personalBusiness(): ?Business
    {
        return $this->businesses()
            ->where('is_personal', true)
            ->first();
    }

    /**
     * Switch to the given business.
     */
    public function switchBusiness(Business $business): bool
    {
        if (! $this->belongsToBusiness($business)) {
            return false;
        }

        $this->update(['current_business_id' => $business->id]);
        $this->setRelation('currentBusiness', $business);

        return true;
    }

    /**
     * Determine if the user belongs to the given business.
     */
    public function belongsToBusiness(Business $business): bool
    {
        return $this->businesses()->where('businesses.id', $business->id)->exists();
    }

    /**
     * Determine if the given business is the user's current business.
     */
    public function isCurrentBusiness(Business $business): bool
    {
        return $this->current_business_id === $business->id;
    }

    /**
     * Determine if the user is the owner of the given business.
     */
    public function ownsBusiness(Business $business): bool
    {
        return $this->businessRole($business) === BusinessRole::Owner;
    }

    /**
     * Get the user's role on the given business.
     */
    public function businessRole(Business $business): ?BusinessRole
    {
        return $this->businessMemberships()
            ->where('business_id', $business->id)
            ->first()
            ?->role;
    }

    /**
     * Get the user's businesses as a collection of UserBusiness objects.
     *
     * @return Collection<int, UserBusiness>
     */
    public function toUserBusinesses(bool $includeCurrent = false): Collection
    {
        return $this->businesses()
            ->get()
            ->map(fn (Business $business) => ! $includeCurrent && $this->isCurrentBusiness($business) ? null : $this->toUserBusiness($business))
            ->filter()
            ->values();
    }

    /**
     * Get the user's business as a UserBusiness object.
     */
    public function toUserBusiness(Business $business): UserBusiness
    {
        $role = $this->businessRole($business);

        return new UserBusiness(
            id: $business->id,
            name: $business->name,
            slug: $business->slug,
            isPersonal: $business->is_personal,
            role: $role?->value,
            roleLabel: $role?->label(),
            isCurrent: $this->isCurrentBusiness($business),
        );
    }

    /**
     * Get the standard permissions for a business as a BusinessPermissions object.
     */
    public function toBusinessPermissions(Business $business): BusinessPermissions
    {
        $role = $this->businessRole($business);

        return new BusinessPermissions(
            canUpdateBusiness: $role?->hasPermission(BusinessPermission::UpdateBusiness) ?? false,
            canDeleteBusiness: $role?->hasPermission(BusinessPermission::DeleteBusiness) ?? false,
            canAddMember: $role?->hasPermission(BusinessPermission::AddMember) ?? false,
            canUpdateMember: $role?->hasPermission(BusinessPermission::UpdateMember) ?? false,
            canRemoveMember: $role?->hasPermission(BusinessPermission::RemoveMember) ?? false,
            canCreateInvitation: $role?->hasPermission(BusinessPermission::CreateInvitation) ?? false,
            canCancelInvitation: $role?->hasPermission(BusinessPermission::CancelInvitation) ?? false,
        );
    }

    public function fallbackBusiness(?Business $excluding = null): ?Business
    {
        return $this->businesses()
            ->when($excluding, fn ($query) => $query->where('businesses.id', '!=', $excluding->id))
            ->orderByRaw('LOWER(businesses.name)')
            ->first();
    }

    /**
     * Determine if the user has the given permission on the business.
     */
    public function hasBusinessPermission(Business $business, BusinessPermission $permission): bool
    {
        return $this->businessRole($business)?->hasPermission($permission) ?? false;
    }
}
