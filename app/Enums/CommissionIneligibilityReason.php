<?php

declare(strict_types=1);

namespace App\Enums;

enum CommissionIneligibilityReason: string
{
    case NO_REFERRAL_ATTRIBUTION = 'no_referral_attribution';
    case ORDER_NOT_CONFIRMED = 'order_not_confirmed';
    case ORDER_CANCELLED = 'order_cancelled';
    case ORDER_NOT_FULLY_PAID = 'order_not_fully_paid';
    case ORDER_TOTAL_ZERO = 'order_total_zero';
    case COMMISSION_ALREADY_EXISTS = 'commission_already_exists';
    case REFERRAL_ATTRIBUTION_INVALID = 'referral_attribution_invalid';

    /**
     * Human-readable explanation suitable for staff and finance UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::NO_REFERRAL_ATTRIBUTION => 'This order was placed directly and has no referring driver or partner attribution.',
            self::ORDER_NOT_CONFIRMED => 'Order has not been confirmed yet.',
            self::ORDER_CANCELLED => 'Order is cancelled and void.',
            self::ORDER_NOT_FULLY_PAID => 'Customer has not fully paid the order balance.',
            self::ORDER_TOTAL_ZERO => 'Order total amount is zero.',
            self::COMMISSION_ALREADY_EXISTS => 'A commission has already been calculated and recorded for this order.',
            self::REFERRAL_ATTRIBUTION_INVALID => 'Referral attribution associated with this order is invalid or cancelled.',
        };
    }
}
