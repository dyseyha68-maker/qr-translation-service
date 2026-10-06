<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\User;
use App\Models\Vehicle;

class VehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::VEHICLES_VIEW->value);
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        return $user->can(PermissionName::VEHICLES_VIEW->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::VEHICLES_CREATE->value);
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return $user->can(PermissionName::VEHICLES_UPDATE->value);
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->can(PermissionName::VEHICLES_DELETE->value);
    }
}
