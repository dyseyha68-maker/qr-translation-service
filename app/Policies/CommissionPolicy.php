<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\CommissionStatus;
use App\Enums\PermissionName;
use App\Models\Commission;
use App\Models\User;

class CommissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::COMMISSIONS_VIEW->value);
    }

    public function view(User $user, Commission $commission): bool
    {
        return $user->can(PermissionName::COMMISSIONS_VIEW->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::COMMISSIONS_CREATE->value);
    }

    public function approve(User $user, Commission $commission): bool
    {
        if ($commission->status !== CommissionStatus::EARNED) {
            return false;
        }

        return $user->can(PermissionName::COMMISSIONS_APPROVE->value);
    }

    public function pay(User $user, Commission $commission): bool
    {
        if ($commission->status !== CommissionStatus::APPROVED) {
            return false;
        }

        return $user->can(PermissionName::COMMISSIONS_PAY->value);
    }

    public function reverse(User $user, Commission $commission): bool
    {
        if ($commission->status === CommissionStatus::REVERSED) {
            return false;
        }

        return $user->can(PermissionName::COMMISSIONS_REVERSE->value);
    }

    public function viewReports(User $user): bool
    {
        return $user->can(PermissionName::COMMISSIONS_REPORTS->value);
    }

    public function delete(User $user, Commission $commission): bool
    {
        // Historical financial records can never be hard-deleted
        return false;
    }
}
