<?php

declare(strict_types=1);

namespace App\Enums;

enum CommissionPaymentMethod: string
{
    case CASH = 'cash';
    case BANK_TRANSFER = 'bank_transfer';
    case OTHER = 'other';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash (Counter Payout)',
            self::BANK_TRANSFER => 'Bank / Bakong Transfer',
            self::OTHER => 'Other',
        };
    }
}
