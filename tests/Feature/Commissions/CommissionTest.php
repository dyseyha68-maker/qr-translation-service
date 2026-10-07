<?php

declare(strict_types=1);

namespace Tests\Feature\Commissions;

use App\Enums\CommissionIneligibilityReason;
use App\Enums\CommissionPaymentMethod;
use App\Enums\CommissionStatus;
use App\Enums\DriverStatus;
use App\Enums\OrderStatus;
use App\Enums\PartnerStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentRecordStatus;
use App\Enums\ReferralAttributionStatus;
use App\Enums\ReferralCodeStatus;
use App\Enums\RoleName;
use App\Enums\VehicleStatus;
use App\Models\Commission;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Lead;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ReferralAttribution;
use App\Models\ReferralCode;
use App\Models\ReferralEvent;
use App\Models\ReferralPartner;
use App\Models\TelegramUser;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\CommissionService;
use App\Services\PaymentService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $finance;
    private User $staff;

    private ReferralPartner $partner;
    private Driver $driver;
    private Vehicle $vehicle;
    private ReferralAttribution $attribution;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(RoleName::ADMIN->value);

        $this->finance = User::factory()->create();
        $this->finance->assignRole(RoleName::FINANCE->value);

        $this->staff = User::factory()->create();
        $this->staff->assignRole(RoleName::STAFF->value);

        $this->partner = ReferralPartner::factory()->create([
            'status' => PartnerStatus::ACTIVE,
            'commission_rate' => 10.00,
        ]);
        $this->driver = Driver::factory()->create([
            'referral_partner_id' => $this->partner->id,
            'status' => DriverStatus::ACTIVE,
        ]);
        $this->vehicle = Vehicle::factory()->create([
            'driver_id' => $this->driver->id,
            'status' => VehicleStatus::ACTIVE,
        ]);

        $refCode = ReferralCode::factory()->create([
            'referral_partner_id' => $this->partner->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => ReferralCodeStatus::ACTIVE,
        ]);

        $tgUser = TelegramUser::factory()->create();
        $customer = Customer::factory()->create(['telegram_user_id' => $tgUser->id]);
        $event = ReferralEvent::factory()->create([
            'referral_code_id' => $refCode->id,
            'telegram_user_id' => $tgUser->id,
        ]);

        $this->attribution = ReferralAttribution::factory()->create([
            'referral_event_id' => $event->id,
            'referral_code_id' => $refCode->id,
            'telegram_user_id' => $tgUser->id,
            'customer_id' => $customer->id,
            'referral_partner_id' => $this->partner->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'attribution_status' => ReferralAttributionStatus::ACTIVE,
        ]);
    }

    private function createAttributedOrder(float $total = 100.00, OrderStatus $status = OrderStatus::CONFIRMED): Order
    {
        $order = Order::factory()->create([
            'referral_attribution_id' => $this->attribution->id,
            'customer_id' => $this->attribution->customer_id,
            'status' => $status,
            'subtotal' => $total,
            'discount' => 0.00,
            'total_amount' => $total,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'quantity' => 1,
            'unit_price' => $total,
            'subtotal' => $total,
        ]);

        return $order;
    }

    private function fullyPayOrder(Order $order): Payment
    {
        $paymentService = app(PaymentService::class);

        return $paymentService->recordPayment($order, [
            'amount' => (float) $order->total_amount,
            'payment_method' => PaymentMethod::CASH->value,
            'paid_at' => now()->format('Y-m-d H:i:s'),
        ], $this->finance);
    }

    // ------------------------------------------------------------------------
    // 1. Commission Qualification Conditions
    // ------------------------------------------------------------------------

    public function test_confirmed_attributed_and_fully_paid_order_automatically_earns_commission(): void
    {
        $order = $this->createAttributedOrder(150.00, OrderStatus::CONFIRMED);

        $this->assertNull($order->commission);

        // Paying fully automatically triggers commission creation via PaymentService
        $this->fullyPayOrder($order);

        $order->refresh();
        $this->assertTrue($order->isFullyPaid());
        $this->assertNotNull($order->commission);

        $commission = $order->commission;
        $this->assertEquals(CommissionStatus::EARNED, $commission->status);
        $this->assertEquals(150.00, (float) $commission->eligible_amount);
        $this->assertEquals(10.00, (float) $commission->commission_rate);
        $this->assertEquals(15.00, (float) $commission->commission_amount); // 10% of 150 = 15.00
        $this->assertEquals($this->partner->id, $commission->referral_partner_id);
        $this->assertEquals($this->driver->id, $commission->driver_id);
        $this->assertEquals($this->vehicle->id, $commission->vehicle_id);
        $this->assertNotNull($commission->earned_at);
    }

    public function test_pending_order_does_not_earn_commission(): void
    {
        $commissionService = app(CommissionService::class);
        $order = $this->createAttributedOrder(100.00, OrderStatus::PENDING);

        // Record a payment directly
        Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => 100.00,
            'status' => PaymentRecordStatus::PAID,
        ]);

        $eligibility = $commissionService->checkEligibility($order);
        $this->assertFalse($eligibility['eligible']);
        $this->assertEquals(CommissionIneligibilityReason::ORDER_NOT_CONFIRMED, $eligibility['reason']);

        $commission = $commissionService->evaluateAndEarnForOrder($order);
        $this->assertNull($commission);
    }

    public function test_cancelled_order_does_not_earn_commission(): void
    {
        $commissionService = app(CommissionService::class);
        $order = $this->createAttributedOrder(100.00, OrderStatus::CANCELLED);

        Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => 100.00,
            'status' => PaymentRecordStatus::PAID,
        ]);

        $eligibility = $commissionService->checkEligibility($order);
        $this->assertFalse($eligibility['eligible']);
        $this->assertEquals(CommissionIneligibilityReason::ORDER_CANCELLED, $eligibility['reason']);

        $commission = $commissionService->evaluateAndEarnForOrder($order);
        $this->assertNull($commission);
    }

    public function test_unpaid_or_partially_paid_order_does_not_earn_commission(): void
    {
        $commissionService = app(CommissionService::class);
        $order = $this->createAttributedOrder(100.00, OrderStatus::CONFIRMED);

        // Record partial payment ($40 of $100)
        Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => 40.00,
            'status' => PaymentRecordStatus::PAID,
        ]);

        $eligibility = $commissionService->checkEligibility($order);
        $this->assertFalse($eligibility['eligible']);
        $this->assertEquals(CommissionIneligibilityReason::ORDER_NOT_FULLY_PAID, $eligibility['reason']);

        $commission = $commissionService->evaluateAndEarnForOrder($order);
        $this->assertNull($commission);
    }

    public function test_order_without_referral_attribution_does_not_earn_commission(): void
    {
        $commissionService = app(CommissionService::class);
        $order = Order::factory()->create([
            'referral_attribution_id' => null,
            'status' => OrderStatus::CONFIRMED,
            'total_amount' => 100.00,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => 100.00,
            'status' => PaymentRecordStatus::PAID,
        ]);

        $eligibility = $commissionService->checkEligibility($order);
        $this->assertFalse($eligibility['eligible']);
        $this->assertEquals(CommissionIneligibilityReason::NO_REFERRAL_ATTRIBUTION, $eligibility['reason']);

        $commission = $commissionService->evaluateAndEarnForOrder($order);
        $this->assertNull($commission);
    }

    public function test_zero_amount_order_does_not_earn_commission(): void
    {
        $commissionService = app(CommissionService::class);
        $order = Order::factory()->create([
            'referral_attribution_id' => $this->attribution->id,
            'status' => OrderStatus::CONFIRMED,
            'subtotal' => 0.00,
            'discount' => 0.00,
            'total_amount' => 0.00,
        ]);

        $eligibility = $commissionService->checkEligibility($order);
        $this->assertFalse($eligibility['eligible']);
        $this->assertEquals(CommissionIneligibilityReason::ORDER_TOTAL_ZERO, $eligibility['reason']);

        $commission = $commissionService->evaluateAndEarnForOrder($order);
        $this->assertNull($commission);
    }

    // ------------------------------------------------------------------------
    // 2. Attribution Ownership & Historical Stability
    // ------------------------------------------------------------------------

    public function test_commission_attribution_is_bound_to_order_referral_attribution(): void
    {
        $order = $this->createAttributedOrder(200.00, OrderStatus::CONFIRMED);
        $this->fullyPayOrder($order);

        $order->refresh();
        $commission = $order->commission;

        $this->assertEquals($this->attribution->id, $commission->referral_attribution_id);
        $this->assertEquals($this->attribution->referral_partner_id, $commission->referral_partner_id);
        $this->assertEquals($this->attribution->driver_id, $commission->driver_id);
        $this->assertEquals($this->attribution->vehicle_id, $commission->vehicle_id);
    }

    public function test_subsequent_partner_rate_change_does_not_alter_existing_commission(): void
    {
        $order = $this->createAttributedOrder(100.00, OrderStatus::CONFIRMED);
        $this->fullyPayOrder($order);

        $commission = $order->fresh()->commission;
        $this->assertEquals(10.00, (float) $commission->commission_amount);

        // Partner updates their commission rate to 20%
        $this->partner->update(['commission_rate' => 20.00]);

        // Existing commission remains strictly untouched at 10% / $10.00
        $commission->refresh();
        $this->assertEquals(10.00, (float) $commission->commission_rate);
        $this->assertEquals(10.00, (float) $commission->commission_amount);
    }

    public function test_idempotent_commission_creation_prevents_duplicate_commissions(): void
    {
        $commissionService = app(CommissionService::class);
        $order = $this->createAttributedOrder(100.00, OrderStatus::CONFIRMED);
        $this->fullyPayOrder($order);

        $this->assertEquals(1, Commission::where('order_id', $order->id)->count());

        // Call evaluation again
        $commission2 = $commissionService->evaluateAndEarnForOrder($order);

        $this->assertEquals(1, Commission::where('order_id', $order->id)->count());
        $this->assertEquals($order->fresh()->commission->id, $commission2->id);
    }

    // ------------------------------------------------------------------------
    // 3. State Transitions & Lifecycle (EARNED -> APPROVED -> PAID)
    // ------------------------------------------------------------------------

    public function test_commission_lifecycle_earned_to_approved_to_paid(): void
    {
        $commissionService = app(CommissionService::class);
        $order = $this->createAttributedOrder(100.00, OrderStatus::CONFIRMED);
        $this->fullyPayOrder($order);

        $commission = $order->fresh()->commission;
        $this->assertEquals(CommissionStatus::EARNED, $commission->status);

        // Step 1: Approve
        $approved = $commissionService->approveCommission($commission, $this->admin);
        $this->assertInstanceOf(Commission::class, $approved);
        $commission->refresh();
        $this->assertEquals(CommissionStatus::APPROVED, $commission->status);
        $this->assertEquals($this->admin->id, $commission->approved_by);
        $this->assertNotNull($commission->approved_at);

        // Step 2: Record Payout
        $payment = $commissionService->recordPayout($commission, [
            'payment_method' => CommissionPaymentMethod::CASH->value,
            'payment_reference' => 'CASH-REC-001',
            'paid_at' => now()->format('Y-m-d'),
            'paid_to' => $this->driver->name,
            'notes' => 'Paid in cash at office counter',
        ], $this->finance);

        $this->assertNotNull($payment);
        $this->assertEquals(10.00, (float) $payment->amount);
        $this->assertEquals($this->finance->id, $payment->created_by);

        $commission->refresh();
        $this->assertEquals(CommissionStatus::PAID, $commission->status);
        $this->assertEquals($this->finance->id, $commission->paid_by);
        $this->assertNotNull($commission->paid_at);
        $this->assertCount(1, $commission->payments);
    }

    public function test_cannot_record_payout_on_unapproved_commission(): void
    {
        $commissionService = app(CommissionService::class);
        $order = $this->createAttributedOrder(100.00, OrderStatus::CONFIRMED);
        $this->fullyPayOrder($order);

        $commission = $order->fresh()->commission;
        $this->assertEquals(CommissionStatus::EARNED, $commission->status);

        // Attempting to pay directly while still EARNED throws ValidationException
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $commissionService->recordPayout($commission, [
            'payment_method' => CommissionPaymentMethod::CASH->value,
            'paid_at' => now()->format('Y-m-d'),
            'paid_to' => $this->driver->name,
        ], $this->finance);
    }

    public function test_commission_can_be_manually_reversed(): void
    {
        $commissionService = app(CommissionService::class);
        $order = $this->createAttributedOrder(100.00, OrderStatus::CONFIRMED);
        $this->fullyPayOrder($order);

        $commission = $order->fresh()->commission;
        $this->assertEquals(CommissionStatus::EARNED, $commission->status);

        $reversed = $commissionService->reverseCommission($commission, $this->admin, 'Duplicate entry resolved');
        $this->assertNotNull($reversed);
        $this->assertInstanceOf(Commission::class, $reversed);

        $commission->refresh();
        $this->assertEquals(CommissionStatus::REVERSED, $commission->status);
        $this->assertEquals('Duplicate entry resolved', $commission->reversal_reason);
        $this->assertEquals($this->admin->id, $commission->reversed_by);
        $this->assertNotNull($commission->reversed_at);
    }

    public function test_customer_payment_refund_automatically_reverses_commission(): void
    {
        $order = $this->createAttributedOrder(100.00, OrderStatus::CONFIRMED);
        $payment = $this->fullyPayOrder($order);

        $commission = $order->fresh()->commission;
        $this->assertEquals(CommissionStatus::EARNED, $commission->status);

        // Admin refunds the customer payment
        $paymentService = app(PaymentService::class);
        $paymentService->refundPayment($payment, $this->admin, 'Customer cancelled translation request');

        $commission->refresh();
        $this->assertEquals(CommissionStatus::REVERSED, $commission->status);
        $this->assertStringContainsString('order refund', strtolower($commission->reversal_reason ?? ''));
    }

    // ------------------------------------------------------------------------
    // 4. RBAC & HTTP Endpoints
    // ------------------------------------------------------------------------

    public function test_admin_and_finance_can_view_commissions_index(): void
    {
        $order = $this->createAttributedOrder(100.00, OrderStatus::CONFIRMED);
        $this->fullyPayOrder($order);

        $this->actingAs($this->admin)
            ->get(route('commissions.index'))
            ->assertOk();

        $this->actingAs($this->finance)
            ->get(route('commissions.index'))
            ->assertOk();
    }

    public function test_staff_cannot_approve_or_pay_commissions(): void
    {
        $order = $this->createAttributedOrder(100.00, OrderStatus::CONFIRMED);
        $this->fullyPayOrder($order);
        $commission = $order->fresh()->commission;

        // Staff attempts approve
        $this->actingAs($this->staff)
            ->patch(route('commissions.approve', $commission->id))
            ->assertForbidden();

        // Admin approves it
        $this->actingAs($this->admin)
            ->patch(route('commissions.approve', $commission->id))
            ->assertRedirect();

        $commission->refresh();
        $this->assertEquals(CommissionStatus::APPROVED, $commission->status);

        // Staff attempts payout
        $this->actingAs($this->staff)
            ->post(route('commissions.payout', $commission->id), [
                'payment_method' => CommissionPaymentMethod::CASH->value,
                'paid_at' => now()->format('Y-m-d'),
                'paid_to' => $this->driver->name,
            ])
            ->assertForbidden();
    }

    public function test_finance_can_approve_and_record_payout_via_http(): void
    {
        $order = $this->createAttributedOrder(100.00, OrderStatus::CONFIRMED);
        $this->fullyPayOrder($order);
        $commission = $order->fresh()->commission;

        // Finance approves
        $this->actingAs($this->finance)
            ->patch(route('commissions.approve', $commission->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $commission->refresh();
        $this->assertEquals(CommissionStatus::APPROVED, $commission->status);

        // Finance records payout
        $this->actingAs($this->finance)
            ->post(route('commissions.payout', $commission->id), [
                'payment_method' => CommissionPaymentMethod::BANK_TRANSFER->value,
                'payment_reference' => 'BANK-TXN-9988',
                'paid_at' => now()->format('Y-m-d'),
                'paid_to' => $this->driver->name,
                'notes' => 'Disbursed via bank transfer',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $commission->refresh();
        $this->assertEquals(CommissionStatus::PAID, $commission->status);
        $this->assertCount(1, $commission->payments);
    }

    public function test_authorized_user_can_view_commission_reports(): void
    {
        $order = $this->createAttributedOrder(100.00, OrderStatus::CONFIRMED);
        $this->fullyPayOrder($order);

        $this->actingAs($this->admin)
            ->get(route('commissions.reports'))
            ->assertOk();

        $this->actingAs($this->finance)
            ->get(route('commissions.reports'))
            ->assertOk();
    }

    public function test_admin_can_adjust_commission_rate_on_earned_commission(): void
    {
        $order = $this->createAttributedOrder(200.00, OrderStatus::CONFIRMED);
        $this->fullyPayOrder($order);

        $commission = Commission::where('order_id', $order->id)->firstOrFail();
        $this->assertEquals(10.0, (float) $commission->commission_rate);
        $this->assertEquals(20.00, (float) $commission->commission_amount);

        // Admin adjusts rate from 10% to 5%
        $response = $this->actingAs($this->admin)
            ->patch(route('commissions.adjust-rate', $commission->id), [
                'commission_rate' => 5.0,
                'reason' => 'Repeat client negotiated bulk rate',
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        $commission->refresh();
        $this->assertEquals(5.0, (float) $commission->commission_rate);
        $this->assertEquals(10.00, (float) $commission->commission_amount);
        $this->assertStringContainsString('Repeat client negotiated bulk rate', (string) $commission->adjustment_notes);
    }

    public function test_staff_cannot_adjust_commission_rate(): void
    {
        $order = $this->createAttributedOrder(100.00, OrderStatus::CONFIRMED);
        $this->fullyPayOrder($order);

        $commission = Commission::where('order_id', $order->id)->firstOrFail();

        $this->actingAs($this->staff)
            ->patch(route('commissions.adjust-rate', $commission->id), [
                'commission_rate' => 5.0,
                'reason' => 'Unauthorized attempt',
            ])
            ->assertForbidden();

        $commission->refresh();
        $this->assertEquals(10.0, (float) $commission->commission_rate);
    }

    public function test_cannot_adjust_rate_on_approved_or_paid_commission(): void
    {
        $order = $this->createAttributedOrder(100.00, OrderStatus::CONFIRMED);
        $this->fullyPayOrder($order);

        $commission = Commission::where('order_id', $order->id)->firstOrFail();

        // Approve it first
        $this->actingAs($this->admin)
            ->patch(route('commissions.approve', $commission->id));

        $commission->refresh();
        $this->assertEquals(CommissionStatus::APPROVED, $commission->status);

        // Attempt to adjust rate on approved commission must be rejected
        $this->actingAs($this->admin)
            ->patch(route('commissions.adjust-rate', $commission->id), [
                'commission_rate' => 5.0,
            ])
            ->assertForbidden();
    }
}
