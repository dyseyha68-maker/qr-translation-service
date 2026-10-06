<?php

declare(strict_types=1);

namespace App\Enums;

enum PermissionName: string
{
    // Phase 1: Users & Roles
    case USERS_VIEW = 'users.view';
    case USERS_CREATE = 'users.create';
    case USERS_UPDATE = 'users.update';
    case USERS_DELETE = 'users.delete';

    case ROLES_VIEW = 'roles.view';
    case ROLES_ASSIGN = 'roles.assign';

    case DASHBOARD_VIEW = 'dashboard.view';

    // Phase 2: Referral Partners
    case PARTNERS_VIEW = 'partners.view';
    case PARTNERS_CREATE = 'partners.create';
    case PARTNERS_UPDATE = 'partners.update';
    case PARTNERS_DELETE = 'partners.delete';

    // Phase 2: Drivers
    case DRIVERS_VIEW = 'drivers.view';
    case DRIVERS_CREATE = 'drivers.create';
    case DRIVERS_UPDATE = 'drivers.update';
    case DRIVERS_DELETE = 'drivers.delete';

    // Phase 2: Vehicles
    case VEHICLES_VIEW = 'vehicles.view';
    case VEHICLES_CREATE = 'vehicles.create';
    case VEHICLES_UPDATE = 'vehicles.update';
    case VEHICLES_DELETE = 'vehicles.delete';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
