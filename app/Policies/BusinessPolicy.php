<?php

namespace App\Policies;

use App\Enums\BusinessPermission;
use App\Models\Business;
use App\Models\User;

class BusinessPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Business $business): bool
    {
        return $user->belongsToBusiness($business);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Business $business): bool
    {
        return $user->hasBusinessPermission($business, BusinessPermission::UpdateBusiness);
    }

    /**
     * Determine whether the user can leave the business.
     */
    public function leave(User $user, Business $business): bool
    {
        return ! $business->is_personal
            && $user->belongsToBusiness($business)
            && ! $user->ownsBusiness($business);
    }

    /**
     * Determine whether the user can add a member to the business.
     */
    public function addMember(User $user, Business $business): bool
    {
        return $user->hasBusinessPermission($business, BusinessPermission::AddMember);
    }

    /**
     * Determine whether the user can update a member's role in the business.
     */
    public function updateMember(User $user, Business $business): bool
    {
        return $user->hasBusinessPermission($business, BusinessPermission::UpdateMember);
    }

    /**
     * Determine whether the user can remove a member from the business.
     */
    public function removeMember(User $user, Business $business): bool
    {
        return $user->hasBusinessPermission($business, BusinessPermission::RemoveMember);
    }

    /**
     * Determine whether the user can invite members to the business.
     */
    public function inviteMember(User $user, Business $business): bool
    {
        return $user->hasBusinessPermission($business, BusinessPermission::CreateInvitation);
    }

    /**
     * Determine whether the user can cancel invitations.
     */
    public function cancelInvitation(User $user, Business $business): bool
    {
        return $user->hasBusinessPermission($business, BusinessPermission::CancelInvitation);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Business $business): bool
    {
        return ! $business->is_personal && $user->hasBusinessPermission($business, BusinessPermission::DeleteBusiness);
    }
}
