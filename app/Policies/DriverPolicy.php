<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Driver;
use App\Models\User;

class DriverPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::DRIVERS_VIEW->value);
    }

    public function view(User $user, Driver $driver): bool
    {
        return $user->can(PermissionName::DRIVERS_VIEW->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::DRIVERS_CREATE->value);
    }

    public function update(User $user, Driver $driver): bool
    {
        return $user->can(PermissionName::DRIVERS_UPDATE->value);
    }

    public function delete(User $user, Driver $driver): bool
    {
        return $user->can(PermissionName::DRIVERS_DELETE->value);
    }
}
