<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Enums\PermissionName;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::ORDERS_VIEW->value);
    }

    public function view(User $user, Order $order): bool
    {
        return $user->can(PermissionName::ORDERS_VIEW->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::ORDERS_CREATE->value);
    }

    public function update(User $user, Order $order): bool
    {
        if ($order->status === OrderStatus::CANCELLED || $order->status === OrderStatus::COMPLETED) {
            return false;
        }

        return $user->can(PermissionName::ORDERS_UPDATE->value);
    }

    public function confirm(User $user, Order $order): bool
    {
        if ($order->status !== OrderStatus::PENDING) {
            return false;
        }

        return $user->can(PermissionName::ORDERS_CONFIRM->value);
    }

    public function cancel(User $user, Order $order): bool
    {
        if ($order->status === OrderStatus::CANCELLED || $order->status === OrderStatus::COMPLETED) {
            return false;
        }

        return $user->can(PermissionName::ORDERS_CANCEL->value);
    }

    public function complete(User $user, Order $order): bool
    {
        if ($order->status !== OrderStatus::CONFIRMED) {
            return false;
        }

        return $user->can(PermissionName::ORDERS_COMPLETE->value);
    }

    public function delete(User $user, Order $order): bool
    {
        // Strictly protect historical financial records: never allow deleting if payments exist
        if ($order->payments()->exists()) {
            return false;
        }

        return $user->can(PermissionName::ORDERS_DELETE->value);
    }
}
