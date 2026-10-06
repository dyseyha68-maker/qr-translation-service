<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::LEADS_VIEW->value);
    }

    public function view(User $user, Lead $lead): bool
    {
        return $user->can(PermissionName::LEADS_VIEW->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::LEADS_CREATE->value);
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->can(PermissionName::LEADS_UPDATE->value);
    }

    public function assign(User $user, Lead $lead): bool
    {
        return $user->can(PermissionName::LEADS_ASSIGN->value);
    }

    public function changeAttribution(User $user, Lead $lead): bool
    {
        return $user->can(PermissionName::LEADS_CHANGE_ATTRIBUTION->value);
    }

    public function addNote(User $user, Lead $lead): bool
    {
        return $user->can(PermissionName::LEADS_ADD_NOTE->value);
    }
}
