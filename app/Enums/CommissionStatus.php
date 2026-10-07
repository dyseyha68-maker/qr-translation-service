<?php

declare(strict_types=1);

namespace App\Enums;

enum CommissionStatus: string
{
    case PENDING = 'pending';
    case EARNED = 'earned';
    case APPROVED = 'approved';
    case PAID = 'paid';
    case REVERSED = 'reversed';

    /**
     * Determine if a status transition is valid according to business lifecycle rules.
     */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::PENDING => in_array($target, [self::EARNED, self::REVERSED], true),
            self::EARNED => in_array($target, [self::APPROVED, self::REVERSED], true),
            self::APPROVED => in_array($target, [self::PAID, self::REVERSED], true),
            self::PAID => $target === self::REVERSED,
            self::REVERSED => false,
        };
    }

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
            self::PENDING => 'Pending',
            self::EARNED => 'Earned',
            self::APPROVED => 'Approved',
            self::PAID => 'Paid',
            self::REVERSED => 'Reversed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::EARNED => 'amber',
            self::APPROVED => 'blue',
            self::PAID => 'emerald',
            self::REVERSED => 'rose',
        };
    }
}
