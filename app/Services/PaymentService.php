<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentRecordStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    /**
     * Generate a unique sequential payment reference (e.g. PAY-2026-000001).
     */
    public function generatePaymentReference(): string
    {
        $year = Carbon::now()->format('Y');
        $prefix = "PAY-{$year}-";

        return DB::transaction(function () use ($prefix) {
            $latestPayment = Payment::where('payment_reference', 'like', "{$prefix}%")
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            $sequence = 1;
            if ($latestPayment && preg_match('/-(\d+)$/', $latestPayment->payment_reference, $matches)) {
                $sequence = (int) $matches[1] + 1;
            }

            return $prefix . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Record a new payment against an order.
     * Enforces concurrency safety, overpayment rejection, and financial audit recording.
     *
     * @param array{
     *     amount: float|int|string,
     *     payment_method: PaymentMethod|string,
     *     payment_reference?: ?string,
     *     paid_at?: ?string,
     *     notes?: ?string
     * } $data
     * @throws ValidationException
     */
    public function recordPayment(Order $order, array $data, User $receiver): Payment
    {
        return DB::transaction(function () use ($order, $data, $receiver) {
            // Concurrency lock on order
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->status === OrderStatus::CANCELLED) {
                throw ValidationException::withMessages([
                    'order' => 'Payments cannot be recorded on cancelled orders.',
                ]);
            }

            $amount = round((float) $data['amount'], 2);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment amount must be greater than zero.',
                ]);
            }

            $outstanding = $lockedOrder->outstanding_amount;

            if ($outstanding <= 0.0001) {
                throw ValidationException::withMessages([
                    'amount' => 'This order is already fully paid. No further payments can be recorded.',
                ]);
            }

            // Enforce overpayment rejection
            if ($amount > ($outstanding + 0.0001)) {
                throw ValidationException::withMessages([
                    'amount' => "Payment amount (\${$amount}) exceeds the remaining outstanding amount (\${$outstanding}). Overpayment is not permitted.",
                ]);
            }

            $reference = !empty($data['payment_reference'])
                ? trim((string) $data['payment_reference'])
                : $this->generatePaymentReference();

            // Validate reference uniqueness if custom reference provided
            $existingRef = Payment::where('payment_reference', $reference)->exists();
            if ($existingRef) {
                throw ValidationException::withMessages([
                    'payment_reference' => "Payment reference '{$reference}' has already been recorded.",
                ]);
            }

            $method = $data['payment_method'] instanceof PaymentMethod
                ? $data['payment_method']
                : PaymentMethod::from((string) $data['payment_method']);

            $paidAt = !empty($data['paid_at'])
                ? Carbon::parse($data['paid_at'])
                : Carbon::now();

            $payment = Payment::create([
                'order_id' => $lockedOrder->id,
                'payment_reference' => $reference,
                'amount' => $amount,
                'currency' => $lockedOrder->currency,
                'payment_method' => $method,
                'status' => PaymentRecordStatus::PAID,
                'paid_at' => $paidAt,
                'received_by' => $receiver->id,
                'notes' => $data['notes'] ?? null,
            ]);

            // Phase 6 Integration: Evaluate commission qualification when order reaches fully paid
            $freshOrder = $lockedOrder->fresh();
            if ($freshOrder && $freshOrder->isFullyPaid()) {
                app(CommissionService::class)->evaluateAndEarnForOrder($freshOrder, $receiver);
            }

            return $payment;
        });
    }

    /**
     * Process a refund on a valid payment record.
     * Marks payment as REFUNDED and preserves financial history.
     *
     * @throws ValidationException
     */
    public function refundPayment(Payment $payment, User $refunder, string $reason): Payment
    {
        return DB::transaction(function () use ($payment, $refunder, $reason) {
            /** @var Payment $lockedPayment */
            $lockedPayment = Payment::where('id', $payment->id)->lockForUpdate()->firstOrFail();

            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $lockedPayment->order_id)->lockForUpdate()->firstOrFail();

            if ($lockedPayment->status === PaymentRecordStatus::REFUNDED) {
                throw ValidationException::withMessages([
                    'payment' => 'This payment record has already been refunded.',
                ]);
            }

            $lockedPayment->update([
                'status' => PaymentRecordStatus::REFUNDED,
                'refunded_at' => Carbon::now(),
                'refund_reason' => trim($reason),
                'refunded_by' => $refunder->id,
            ]);

            // Phase 6 Integration: Reverse commission if order is no longer fully paid
            $freshOrder = $lockedOrder->fresh();
            if ($freshOrder) {
                app(CommissionService::class)->handleOrderRefund($freshOrder, $refunder, $reason);
            }

            return $lockedPayment->fresh();
        });
    }
}
