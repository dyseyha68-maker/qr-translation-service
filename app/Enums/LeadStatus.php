<?php

declare(strict_types=1);

namespace App\Enums;

enum LeadStatus: string
{
    case NEW = 'NEW';
    case CONTACTED = 'CONTACTED';
    case QUOTATION = 'QUOTATION';
    case CONFIRMED = 'CONFIRMED';
    case LOST = 'LOST';
    case CANCELLED = 'CANCELLED';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Determine if the lead is actively open in the intake pipeline.
     */
    public function isActive(): bool
    {
        return in_array($this, [self::NEW, self::CONTACTED, self::QUOTATION], true);
    }

    /**
     * @return array<string>
     */
    public static function activeValues(): array
    {
        return [
            self::NEW->value,
            self::CONTACTED->value,
            self::QUOTATION->value,
        ];
    }
}
