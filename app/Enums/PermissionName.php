<?php

declare(strict_types=1);

namespace App\Enums;

enum PermissionName: string
{
    case USERS_VIEW = 'users.view';
    case USERS_CREATE = 'users.create';
    case USERS_UPDATE = 'users.update';
    case USERS_DELETE = 'users.delete';

    case ROLES_VIEW = 'roles.view';
    case ROLES_ASSIGN = 'roles.assign';

    case DASHBOARD_VIEW = 'dashboard.view';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
