<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentRecordStatus;
use App\Enums\PaymentStatus;
use App\Enums\RoleName;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;
    private User $finance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(RoleName::ADMIN->value);

        $this->staff = User::factory()->create();
        $this->staff->assignRole(RoleName::STAFF->value);

        $this->finance = User::factory()->create();
        $this->finance->assignRole(RoleName::FINANCE->value);
    }

    public function test_record_partial_payment_and_verify_outstanding(): void
    {
        /** @var Order $order */
        $order = Order::factory()->create([
            'subtotal' => 100.00,
            'discount' => 0.00,
            'total_amount' => 100.00,
            'status' => OrderStatus::CONFIRMED,
        ]);

        $payload = [
            'amount' => 40.00,
            'payment_method' => PaymentMethod::CASH->value,
            'notes' => 'Partial deposit',
        ];

        $response = $this->actingAs($this->staff)->post(route('orders.payments.store', $order), $payload);
        $response->assertRedirect();

        $freshOrder = $order->fresh();
        $this->assertEquals(40.00, $freshOrder->paid_amount);
        $this->assertEquals(60.00, $freshOrder->outstanding_amount);
        $this->assertEquals(PaymentStatus::PARTIAL, $freshOrder->payment_status);
        $this->assertFalse($freshOrder->isFullyPaid());
    }

    public function test_full_payment_via_multiple_transactions(): void
    {
        /** @var Order $order */
        $order = Order::factory()->create([
            'subtotal' => 100.00,
            'discount' => 0.00,
            'total_amount' => 100.00,
            'status' => OrderStatus::CONFIRMED,
        ]);

        // Payment 1: $40
        $this->actingAs($this->staff)->post(route('orders.payments.store', $order), [
            'amount' => 40.00,
            'payment_method' => PaymentMethod::CASH->value,
        ])->assertRedirect();

        // Payment 2: $60
        $this->actingAs($this->finance)->post(route('orders.payments.store', $order), [
            'amount' => 60.00,
            'payment_method' => PaymentMethod::KHQR_MANUAL->value,
        ])->assertRedirect();

        $freshOrder = $order->fresh();
        $this->assertEquals(100.00, $freshOrder->paid_amount);
        $this->assertEquals(0.00, $freshOrder->outstanding_amount);
        $this->assertEquals(PaymentStatus::PAID, $freshOrder->payment_status);
        $this->assertTrue($freshOrder->isFullyPaid());
        $this->assertCount(2, $freshOrder->payments);
    }

    public function test_reject_overpayment(): void
    {
        /** @var Order $order */
        $order = Order::factory()->create([
            'total_amount' => 100.00,
            'status' => OrderStatus::CONFIRMED,
        ]);

        // Overpayment on initial payment ($120 on $100 order)
        $response = $this->actingAs($this->staff)->post(route('orders.payments.store', $order), [
            'amount' => 120.00,
            'payment_method' => PaymentMethod::CASH->value,
        ]);

        $response->assertSessionHasErrors(['amount']);
        $this->assertEquals(0, Payment::count());

        // Record valid partial payment of $70
        $this->actingAs($this->staff)->post(route('orders.payments.store', $order), [
            'amount' => 70.00,
            'payment_method' => PaymentMethod::CASH->value,
        ])->assertRedirect();

        // Attempting to record $40 when outstanding is only $30
        $response2 = $this->actingAs($this->staff)->post(route('orders.payments.store', $order), [
            'amount' => 40.00,
            'payment_method' => PaymentMethod::CASH->value,
        ]);

        $response2->assertSessionHasErrors(['amount']);
        $this->assertEquals(1, Payment::count());
    }

    public function test_reject_payment_when_order_already_fully_paid(): void
    {
        /** @var Order $order */
        $order = Order::factory()->create([
            'total_amount' => 100.00,
            'status' => OrderStatus::CONFIRMED,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => 100.00,
            'status' => PaymentRecordStatus::PAID,
        ]);

        $response = $this->actingAs($this->staff)->post(route('orders.payments.store', $order), [
            'amount' => 10.00,
            'payment_method' => PaymentMethod::CASH->value,
        ]);

        $response->assertForbidden();
    }

    public function test_refund_payment_reverts_order_fully_paid_state_and_preserves_history(): void
    {
        /** @var Order $order */
        $order = Order::factory()->create([
            'total_amount' => 100.00,
            'status' => OrderStatus::CONFIRMED,
        ]);

        /** @var Payment $payment */
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => 100.00,
            'status' => PaymentRecordStatus::PAID,
        ]);

        $this->assertTrue($order->fresh()->isFullyPaid());

        // Process refund
        $response = $this->actingAs($this->finance)->patch(route('payments.refund', $payment), [
            'refund_reason' => 'Customer requested cancellation and full refund',
        ]);
        $response->assertRedirect();

        $freshPayment = $payment->fresh();
        $this->assertEquals(PaymentRecordStatus::REFUNDED, $freshPayment->status);
        $this->assertNotNull($freshPayment->refunded_at);
        $this->assertEquals($this->finance->id, $freshPayment->refunded_by);
        $this->assertEquals('Customer requested cancellation and full refund', $freshPayment->refund_reason);

        // Financial history preserved (payment row still exists)
        $this->assertEquals(1, Payment::count());

        // Order is no longer fully paid!
        $freshOrder = $order->fresh();
        $this->assertEquals(0.00, $freshOrder->paid_amount);
        $this->assertEquals(100.00, $freshOrder->outstanding_amount);
        $this->assertEquals(PaymentStatus::REFUNDED, $freshOrder->payment_status);
        $this->assertFalse($freshOrder->isFullyPaid());
    }

    public function test_staff_cannot_refund_payment(): void
    {
        /** @var Order $order */
        $order = Order::factory()->create([
            'total_amount' => 100.00,
            'status' => OrderStatus::CONFIRMED,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => 50.00,
            'status' => PaymentRecordStatus::PAID,
        ]);

        $response = $this->actingAs($this->staff)->patch(route('payments.refund', $payment), [
            'refund_reason' => 'Attempted by staff',
        ]);

        $response->assertForbidden();
        $this->assertEquals(PaymentRecordStatus::PAID, $payment->fresh()->status);
    }

    public function test_cannot_refund_already_refunded_payment(): void
    {
        $payment = Payment::factory()->refunded()->create();

        $response = $this->actingAs($this->finance)->patch(route('payments.refund', $payment), [
            'refund_reason' => 'Second refund attempt',
        ]);

        $response->assertForbidden();
    }
}
