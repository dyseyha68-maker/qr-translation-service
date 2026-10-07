<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Enums\PaymentRecordStatus;
use App\Enums\PermissionName;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::PAYMENTS_VIEW->value);
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->can(PermissionName::PAYMENTS_VIEW->value);
    }

    public function create(User $user, Order $order): bool
    {
        if ($order->status === OrderStatus::CANCELLED) {
            return false;
        }

        if ($order->outstanding_amount <= 0.0001) {
            return false;
        }

        return $user->can(PermissionName::PAYMENTS_CREATE->value);
    }

    public function refund(User $user, Payment $payment): bool
    {
        if ($payment->status === PaymentRecordStatus::REFUNDED) {
            return false;
        }

        return $user->can(PermissionName::PAYMENTS_REFUND->value);
    }
}
