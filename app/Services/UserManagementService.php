<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserManagementService
{
    /**
     * Create a new user and assign their initial role.
     *
     * @param  array{name: string, email: string, password: string, role: string, status: string}  $data
     *
     * @throws AuthorizationException
     */
    public function createUser(array $data, User $creator): User
    {
        // Safety: Only a Super Admin can assign the Super Admin role
        if ($data['role'] === RoleName::SUPER_ADMIN->value && ! $creator->hasRole(RoleName::SUPER_ADMIN->value)) {
            throw new AuthorizationException('Only a Super Administrator can assign the Super Admin role.');
        }

        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'status' => UserStatus::from($data['status']),
                'avatar_path' => $data['avatar_path'] ?? null,
            ]);

            $user->assignRole($data['role']);

            if (isset($data['permissions']) && is_array($data['permissions'])) {
                $user->syncPermissions($data['permissions']);
            }

            return $user;
        });
    }

    /**
     * Update an existing user's profile, status, and role.
     *
     * @param  array{name: string, email: string, password?: ?string, role: string, status: string, permissions?: array<string>, avatar_path?: ?string}  $data
     *
     * @throws AuthorizationException|ValidationException
     */
    public function updateUser(User $targetUser, array $data, User $updater): User
    {
        // Safety: Only a Super Admin can edit a Super Admin account
        if ($targetUser->hasRole(RoleName::SUPER_ADMIN->value) && ! $updater->hasRole(RoleName::SUPER_ADMIN->value)) {
            throw new AuthorizationException('Only a Super Administrator can modify a Super Admin user.');
        }

        // Safety: Only a Super Admin can assign the Super Admin role to someone else
        if ($data['role'] === RoleName::SUPER_ADMIN->value && ! $updater->hasRole(RoleName::SUPER_ADMIN->value)) {
            throw new AuthorizationException('Only a Super Administrator can assign the Super Admin role.');
        }

        // Count current active Super Admins
        $activeSuperAdminCount = User::role(RoleName::SUPER_ADMIN->value)
            ->where('status', UserStatus::ACTIVE->value)
            ->count();

        // Safety: The final active Super Admin cannot be deactivated
        $isCurrentlyActiveSuperAdmin = $targetUser->hasRole(RoleName::SUPER_ADMIN->value) && $targetUser->isActive();
        $isBeingDeactivated = $data['status'] === UserStatus::INACTIVE->value;

        if ($isCurrentlyActiveSuperAdmin && $isBeingDeactivated && $activeSuperAdminCount <= 1) {
            throw ValidationException::withMessages([
                'status' => 'The final active Super Administrator cannot be deactivated.',
            ]);
        }

        // Safety: The final active Super Admin cannot have their Super Admin role stripped
        $isRoleBeingChanged = $data['role'] !== RoleName::SUPER_ADMIN->value;
        if ($isCurrentlyActiveSuperAdmin && $isRoleBeingChanged && $activeSuperAdminCount <= 1) {
            throw ValidationException::withMessages([
                'role' => 'Cannot remove Super Admin privileges from the final active Super Administrator.',
            ]);
        }

        return DB::transaction(function () use ($targetUser, $data) {
            $updatePayload = [
                'name' => $data['name'],
                'email' => $data['email'],
                'status' => UserStatus::from($data['status']),
            ];

            if (! empty($data['password'])) {
                $updatePayload['password'] = Hash::make($data['password']);
            }

            if (array_key_exists('avatar_path', $data)) {
                $updatePayload['avatar_path'] = $data['avatar_path'];
            }

            $targetUser->update($updatePayload);
            $targetUser->syncRoles([$data['role']]);

            if (isset($data['permissions']) && is_array($data['permissions'])) {
                $targetUser->syncPermissions($data['permissions']);
            }

            return $targetUser;
        });
    }

    /**
     * Delete a user with safeguard against deleting the final Super Admin.
     *
     * @throws ValidationException|AuthorizationException
     */
    public function deleteUser(User $targetUser, User $deleter): void
    {
        if ($deleter->id === $targetUser->id) {
            throw ValidationException::withMessages([
                'user' => 'You cannot delete your own account.',
            ]);
        }

        if ($targetUser->hasRole(RoleName::SUPER_ADMIN->value) && ! $deleter->hasRole(RoleName::SUPER_ADMIN->value)) {
            throw new AuthorizationException('Only a Super Administrator can delete a Super Admin user.');
        }

        if ($targetUser->hasRole(RoleName::SUPER_ADMIN->value)) {
            $activeSuperAdminCount = User::role(RoleName::SUPER_ADMIN->value)
                ->where('status', UserStatus::ACTIVE->value)
                ->count();

            if ($activeSuperAdminCount <= 1) {
                throw ValidationException::withMessages([
                    'user' => 'The final active Super Administrator cannot be deleted.',
                ]);
            }
        }

        DB::transaction(function () use ($targetUser) {
            $targetUser->delete();
        });
    }
}
