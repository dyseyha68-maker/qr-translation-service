<?php

declare(strict_types=1);

namespace App\Enums;

enum TelegramEventType: string
{
    case SCAN = 'SCAN';
    case START = 'START';
    case REPEAT_SCAN = 'REPEAT_SCAN';
    case INVALID_CODE = 'INVALID_CODE';
    case INACTIVE_CODE = 'INACTIVE_CODE';
    case UNKNOWN_CODE = 'UNKNOWN_CODE';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
