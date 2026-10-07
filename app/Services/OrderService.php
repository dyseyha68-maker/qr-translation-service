<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LeadStatus;
use App\Enums\OrderStatus;
use App\Models\Lead;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class OrderService
{
    /**
     * Generate a globally unique, collision-resistant order number.
     * Format: ORD-YYYY-000001
     */
    public function generateOrderNumber(): string
    {
        $year = Carbon::now()->format('Y');
        $prefix = "ORD-{$year}-";

        return DB::transaction(function () use ($prefix) {
            /** @var Order|null $latest */
            $latest = Order::where('order_number', 'like', "{$prefix}%")
                ->lockForUpdate()
                ->latest('id')
                ->first();

            $sequence = 1;
            if ($latest && preg_match('/-(\d+)$/', $latest->order_number, $matches)) {
                $sequence = ((int) $matches[1]) + 1;
            }

            do {
                $candidate = sprintf('%s%06d', $prefix, $sequence);
                $sequence++;
            } while (Order::where('order_number', $candidate)->exists());

            return $candidate;
        });
    }

    /**
     * Calculate items subtotal, discount, and total amounts strictly server-side.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array{subtotal: float, discount: float, total: float, calculated_items: array<int, array<string, mixed>>}
     */
    public function calculateTotals(array $items, float $discount = 0.0): array
    {
        $subtotal = 0.0;
        $calculatedItems = [];

        foreach ($items as $item) {
            $qty = max(1, (int) ($item['quantity'] ?? 1));
            $unitPrice = max(0.0, round((float) ($item['unit_price'] ?? 0.0), 2));
            $itemSubtotal = round($qty * $unitPrice, 2);

            $subtotal += $itemSubtotal;
            $calculatedItems[] = [
                'description' => trim((string) ($item['description'] ?? 'Translation Service')),
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'subtotal' => $itemSubtotal,
                'notes' => isset($item['notes']) ? trim((string) $item['notes']) : null,
            ];
        }

        $subtotal = round($subtotal, 2);
        $discount = max(0.0, round($discount, 2));

        if ($discount > $subtotal) {
            throw ValidationException::withMessages([
                'discount' => 'Discount cannot exceed the order subtotal.',
            ]);
        }

        $total = max(0.0, round($subtotal - $discount, 2));

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'calculated_items' => $calculatedItems,
        ];
    }

    /**
     * Create an Order from a Lead with immutable referral attribution binding.
     *
     * @param  array<string, mixed>  $data
     */
    public function createFromLead(Lead $lead, array $data, User $creator): Order
    {
        if ($lead->status === LeadStatus::CANCELLED) {
            throw ValidationException::withMessages([
                'lead_id' => 'Cannot create an order for a cancelled lead.',
            ]);
        }

        // Prevent accidental duplicate orders for the same active opportunity
        $existingOrder = Order::where('lead_id', $lead->id)
            ->where('status', '!=', OrderStatus::CANCELLED->value)
            ->first();

        if ($existingOrder !== null) {
            throw ValidationException::withMessages([
                'lead_id' => "Lead #{$lead->id} already has active Order {$existingOrder->order_number}.",
            ]);
        }

        $items = $data['items'] ?? [];
        if (empty($items)) {
            throw ValidationException::withMessages([
                'items' => 'An order must have at least one line item.',
            ]);
        }

        $discount = (float) ($data['discount'] ?? 0.0);
        $totals = $this->calculateTotals($items, $discount);

        return DB::transaction(function () use ($lead, $data, $creator, $totals) {
            $orderNumber = $this->generateOrderNumber();

            // CRITICAL: referral_attribution_id is copied strictly from lead.
            // Client-submitted attribution values are strictly ignored.
            /** @var Order $order */
            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $lead->customer_id,
                'lead_id' => $lead->id,
                'referral_attribution_id' => $lead->referral_attribution_id,
                'status' => OrderStatus::PENDING,
                'currency' => strtoupper((string) ($data['currency'] ?? 'USD')),
                'subtotal' => $totals['subtotal'],
                'discount' => $totals['discount'],
                'total_amount' => $totals['total'],
                'notes' => isset($data['notes']) ? trim((string) $data['notes']) : null,
                'created_by' => $creator->id,
                'updated_by' => $creator->id,
            ]);

            foreach ($totals['calculated_items'] as $itemData) {
                $order->items()->create($itemData);
            }

            // Record in Lead notes
            $lead->notes()->create([
                'user_id' => $creator->id,
                'note' => "Order #{$order->order_number} created with total \${$order->total_amount}.",
            ]);

            return $order;
        });
    }

    /**
     * Update an order's line items and details.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Order $order, array $data, User $editor): Order
    {
        if ($order->status === OrderStatus::CANCELLED || $order->status === OrderStatus::COMPLETED) {
            throw new InvalidArgumentException("Cannot update an order with status '{$order->status->value}'.");
        }

        $items = $data['items'] ?? [];
        if (empty($items)) {
            throw new InvalidArgumentException('An order must have at least one line item.');
        }

        $discount = (float) ($data['discount'] ?? 0.0);
        $totals = $this->calculateTotals($items, $discount);

        // Disallow reducing total amount below already paid amount
        if ($totals['total'] < $order->paid_amount) {
            throw new InvalidArgumentException("Order total (\${$totals['total']}) cannot be lower than the already paid amount (\${$order->paid_amount}).");
        }

        return DB::transaction(function () use ($order, $data, $editor, $totals) {
            $order->update([
                'currency' => strtoupper((string) ($data['currency'] ?? $order->currency)),
                'subtotal' => $totals['subtotal'],
                'discount' => $totals['discount'],
                'total_amount' => $totals['total'],
                'notes' => isset($data['notes']) ? trim((string) $data['notes']) : null,
                'updated_by' => $editor->id,
            ]);

            // Replace line items
            $order->items()->delete();
            foreach ($totals['calculated_items'] as $itemData) {
                $order->items()->create($itemData);
            }

            return $order->fresh(['items', 'customer', 'referralAttribution']);
        });
    }

    /**
     * Transition order from PENDING to CONFIRMED.
     */
    public function confirm(Order $order, User $actor): Order
    {
        if (! $order->status->canTransitionTo(OrderStatus::CONFIRMED)) {
            throw ValidationException::withMessages([
                'status' => "Cannot confirm an order with status '{$order->status->value}'.",
            ]);
        }

        $order->update([
            'status' => OrderStatus::CONFIRMED,
            'confirmed_at' => Carbon::now(),
            'updated_by' => $actor->id,
        ]);

        if ($order->lead && $order->lead->status === LeadStatus::NEW) {
            $order->lead->update(['status' => LeadStatus::CONFIRMED]);
        }

        return $order;
    }

    /**
     * Transition order from CONFIRMED to COMPLETED.
     */
    public function complete(Order $order, User $actor): Order
    {
        if (! $order->status->canTransitionTo(OrderStatus::COMPLETED)) {
            throw ValidationException::withMessages([
                'status' => "Cannot complete an order with status '{$order->status->value}'.",
            ]);
        }

        $order->update([
            'status' => OrderStatus::COMPLETED,
            'completed_at' => Carbon::now(),
            'updated_by' => $actor->id,
        ]);

        return $order;
    }

    /**
     * Transition order to CANCELLED.
     */
    public function cancel(Order $order, User $actor, ?string $reason = null): Order
    {
        if (! $order->status->canTransitionTo(OrderStatus::CANCELLED)) {
            throw ValidationException::withMessages([
                'status' => "Cannot cancel an order with status '{$order->status->value}'.",
            ]);
        }

        // Cannot cancel if active payments exist
        if ($order->paid_amount > 0) {
            throw ValidationException::withMessages([
                'order' => 'Cannot cancel an order with active customer payments. Please refund payments first.',
            ]);
        }

        $order->update([
            'status' => OrderStatus::CANCELLED,
            'cancelled_at' => Carbon::now(),
            'notes' => $reason ? trim(($order->notes ? "{$order->notes}\n" : '') . "Cancellation reason: {$reason}") : $order->notes,
            'updated_by' => $actor->id,
        ]);

        return $order;
    }

    /**
     * Delete an order with strict safety checks.
     */
    public function delete(Order $order, User $actor): void
    {
        if ($order->payments()->exists()) {
            throw new InvalidArgumentException('Cannot delete an order that has historical payment records.');
        }

        if ($order->status === OrderStatus::COMPLETED) {
            throw new InvalidArgumentException('Cannot delete a completed order.');
        }

        $order->delete();
    }
}
