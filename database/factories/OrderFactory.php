<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => 'ORD-' . Carbon::now()->format('Y') . '-' . fake()->unique()->numerify('######'),
            'customer_id' => Customer::factory(),
            'lead_id' => Lead::factory(),
            'referral_attribution_id' => null,
            'status' => OrderStatus::PENDING,
            'currency' => 'USD',
            'subtotal' => 100.00,
            'discount' => 0.00,
            'total_amount' => 100.00,
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::CONFIRMED,
            'confirmed_at' => Carbon::now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::COMPLETED,
            'confirmed_at' => Carbon::now()->subHour(),
            'completed_at' => Carbon::now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::CANCELLED,
            'cancelled_at' => Carbon::now(),
        ]);
    }
}
