<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleName: string
{
    case SUPER_ADMIN = 'SUPER_ADMIN';
    case ADMIN = 'ADMIN';
    case STAFF = 'STAFF';
    case FINANCE = 'FINANCE';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
