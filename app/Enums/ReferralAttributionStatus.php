<?php

declare(strict_types=1);

namespace App\Enums;

enum ReferralAttributionStatus: string
{
    case ACTIVE = 'ACTIVE';
    case CHANGED = 'CHANGED';
    case CANCELLED = 'CANCELLED';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
