<?php

declare(strict_types=1);

namespace App\Enums;

enum VehicleType: string
{
    case TAXI = 'TAXI';
    case TUK_TUK = 'TUK_TUK';
    case OTHER = 'OTHER';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
