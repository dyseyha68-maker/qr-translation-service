<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentRecordStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_reference' => 'PAY-' . Carbon::now()->format('Y') . '-' . fake()->unique()->numerify('######'),
            'amount' => 50.00,
            'currency' => 'USD',
            'payment_method' => PaymentMethod::CASH,
            'status' => PaymentRecordStatus::PAID,
            'paid_at' => Carbon::now(),
            'received_by' => User::factory(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function refunded(): static
    {
        return $this->state(fn () => [
            'status' => PaymentRecordStatus::REFUNDED,
            'refunded_at' => Carbon::now(),
            'refund_reason' => 'Customer requested refund',
            'refunded_by' => User::factory(),
        ]);
    }
}
