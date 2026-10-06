<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\ReferralPartner;
use App\Models\User;

class ReferralPartnerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::PARTNERS_VIEW->value);
    }

    public function view(User $user, ReferralPartner $partner): bool
    {
        return $user->can(PermissionName::PARTNERS_VIEW->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::PARTNERS_CREATE->value);
    }

    public function update(User $user, ReferralPartner $partner): bool
    {
        return $user->can(PermissionName::PARTNERS_UPDATE->value);
    }

    public function delete(User $user, ReferralPartner $partner): bool
    {
        return $user->can(PermissionName::PARTNERS_DELETE->value);
    }
}
