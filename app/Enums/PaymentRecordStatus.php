<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentRecordStatus: string
{
    case PAID = 'paid';
    case REFUNDED = 'refunded';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
