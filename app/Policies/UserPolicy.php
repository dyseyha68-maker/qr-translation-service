<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $authUser): bool
    {
        return $authUser->can(PermissionName::USERS_VIEW->value);
    }

    /**
     * Determine whether the user can view the specific user.
     */
    public function view(User $authUser, User $targetUser): bool
    {
        return $authUser->can(PermissionName::USERS_VIEW->value);
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $authUser): bool
    {
        return $authUser->can(PermissionName::USERS_CREATE->value);
    }

    /**
     * Determine whether the user can update the specific user.
     */
    public function update(User $authUser, User $targetUser): bool
    {
        if (! $authUser->can(PermissionName::USERS_UPDATE->value)) {
            return false;
        }

        // Only a Super Admin can edit a Super Admin account
        if ($targetUser->hasRole(RoleName::SUPER_ADMIN->value) && ! $authUser->hasRole(RoleName::SUPER_ADMIN->value)) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the specific user.
     */
    public function delete(User $authUser, User $targetUser): bool
    {
        if (! $authUser->can(PermissionName::USERS_DELETE->value)) {
            return false;
        }

        // A user cannot delete their own account
        if ($authUser->id === $targetUser->id) {
            return false;
        }

        // Only a Super Admin can delete a Super Admin account
        if ($targetUser->hasRole(RoleName::SUPER_ADMIN->value) && ! $authUser->hasRole(RoleName::SUPER_ADMIN->value)) {
            return false;
        }

        // The final active Super Admin cannot be deleted
        if ($targetUser->hasRole(RoleName::SUPER_ADMIN->value)) {
            $activeSuperAdmins = User::role(RoleName::SUPER_ADMIN->value)
                ->where('status', UserStatus::ACTIVE->value)
                ->count();

            if ($activeSuperAdmins <= 1) {
                return false;
            }
        }

        return true;
    }
}
