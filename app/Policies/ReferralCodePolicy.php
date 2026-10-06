<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\ReferralCode;
use App\Models\User;

class ReferralCodePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::REFERRAL_CODES_VIEW->value);
    }

    public function view(User $user, ReferralCode $code): bool
    {
        return $user->can(PermissionName::REFERRAL_CODES_VIEW->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::REFERRAL_CODES_CREATE->value);
    }

    public function update(User $user, ReferralCode $code): bool
    {
        return $user->can(PermissionName::REFERRAL_CODES_UPDATE->value);
    }

    public function delete(User $user, ReferralCode $code): bool
    {
        return $user->can(PermissionName::REFERRAL_CODES_DELETE->value);
    }

    public function activate(User $user, ReferralCode $code): bool
    {
        return $user->can(PermissionName::REFERRAL_CODES_ACTIVATE->value);
    }

    public function deactivate(User $user, ReferralCode $code): bool
    {
        return $user->can(PermissionName::REFERRAL_CODES_DEACTIVATE->value);
    }

    public function viewQr(User $user, ReferralCode $code): bool
    {
        return $user->can(PermissionName::REFERRAL_CODES_QR_VIEW->value);
    }

    public function downloadQr(User $user, ReferralCode $code): bool
    {
        return $user->can(PermissionName::REFERRAL_CODES_QR_DOWNLOAD->value);
    }
}
