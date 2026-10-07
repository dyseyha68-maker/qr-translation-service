<?php

declare(strict_types=1);

namespace Tests\Feature\Orders;

use App\Enums\DriverStatus;
use App\Enums\LeadStatus;
use App\Enums\OrderStatus;
use App\Enums\PartnerStatus;
use App\Enums\ReferralAttributionStatus;
use App\Enums\ReferralCodeStatus;
use App\Enums\RoleName;
use App\Enums\VehicleStatus;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Lead;
use App\Models\Order;
use App\Models\ReferralAttribution;
use App\Models\ReferralCode;
use App\Models\ReferralEvent;
use App\Models\ReferralPartner;
use App\Models\TelegramUser;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\OrderService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;
    private User $finance;

    private Lead $leadWithAttribution;
    private ReferralAttribution $attribution;

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

        $partner = ReferralPartner::factory()->create(['status' => PartnerStatus::ACTIVE]);
        $driver = Driver::factory()->create(['referral_partner_id' => $partner->id, 'status' => DriverStatus::ACTIVE]);
        $vehicle = Vehicle::factory()->create(['driver_id' => $driver->id, 'status' => VehicleStatus::ACTIVE]);
        $referralCode = ReferralCode::factory()->create([
            'referral_partner_id' => $partner->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => ReferralCodeStatus::ACTIVE,
        ]);

        $telegramUser = TelegramUser::factory()->create();
        $customer = Customer::factory()->create(['telegram_user_id' => $telegramUser->id]);
        $this->leadWithAttribution = Lead::factory()->create([
            'customer_id' => $customer->id,
            'telegram_user_id' => $telegramUser->id,
            'status' => LeadStatus::CONFIRMED,
        ]);

        $event = ReferralEvent::factory()->create([
            'referral_code_id' => $referralCode->id,
            'telegram_user_id' => $telegramUser->id,
        ]);

        $this->attribution = ReferralAttribution::create([
            'referral_event_id' => $event->id,
            'referral_code_id' => $referralCode->id,
            'customer_id' => $customer->id,
            'lead_id' => $this->leadWithAttribution->id,
            'referral_partner_id' => $partner->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'attribution_status' => ReferralAttributionStatus::ACTIVE,
            'attributed_at' => now(),
        ]);

        $this->leadWithAttribution->update([
            'referral_attribution_id' => $this->attribution->id,
        ]);
    }

    public function test_can_view_orders_index_with_permission(): void
    {
        $response = $this->actingAs($this->staff)->get(route('orders.index'));
        $response->assertOk();
    }

    public function test_create_order_from_lead_copies_customer_lead_and_referral_attribution(): void
    {
        $payload = [
            'lead_id' => $this->leadWithAttribution->id,
            'discount' => 10,
            'notes' => 'Customer requested official seal',
            'items' => [
                [
                    'description' => 'Legal Contract Translation (5 pages)',
                    'quantity' => 5,
                    'unit_price' => 20,
                    'notes' => 'English to Khmer',
                ],
                [
                    'description' => 'Notarization Fee',
                    'quantity' => 1,
                    'unit_price' => 15,
                    'notes' => 'Official stamp',
                ],
            ],
        ];

        $response = $this->actingAs($this->staff)->post(route('orders.store'), $payload);

        $order = Order::first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('orders.show', $order));

        // Subtotal: (5 * 20) + (1 * 15) = 115; Total: 115 - 10 = 105
        $this->assertEquals(115.00, (float) $order->subtotal);
        $this->assertEquals(10.00, (float) $order->discount);
        $this->assertEquals(105.00, (float) $order->total_amount);
        $this->assertEquals($this->leadWithAttribution->customer_id, $order->customer_id);
        $this->assertEquals($this->leadWithAttribution->id, $order->lead_id);
        $this->assertEquals($this->attribution->id, $order->referral_attribution_id);
        $this->assertEquals(OrderStatus::PENDING, $order->status);
        $this->assertMatchesRegularExpression('/^ORD-\d{4}-\d{6}$/', $order->order_number);
        $this->assertCount(2, $order->items);
    }

    public function test_frontend_cannot_override_referral_attribution_id(): void
    {
        // Malicious client attempts to forge attribution id to 9999
        $payload = [
            'lead_id' => $this->leadWithAttribution->id,
            'referral_attribution_id' => 9999,
            'discount' => 0,
            'items' => [
                [
                    'description' => 'General Translation',
                    'quantity' => 1,
                    'unit_price' => 50,
                ],
            ],
        ];

        $this->actingAs($this->staff)->post(route('orders.store'), $payload);

        $order = Order::first();
        $this->assertNotNull($order);
        // Backend strictly used lead's actual attribution
        $this->assertEquals($this->attribution->id, $order->referral_attribution_id);
        $this->assertNotEquals(9999, $order->referral_attribution_id);
    }

    public function test_cannot_create_duplicate_active_order_for_same_lead(): void
    {
        $payload = [
            'lead_id' => $this->leadWithAttribution->id,
            'items' => [
                ['description' => 'Service 1', 'quantity' => 1, 'unit_price' => 50],
            ],
        ];

        // First creation succeeds
        $this->actingAs($this->staff)->post(route('orders.store'), $payload)->assertRedirect();

        // Second creation for same active lead must fail
        $response = $this->actingAs($this->staff)->post(route('orders.store'), $payload);
        $response->assertSessionHasErrors(['lead_id']);
        $this->assertEquals(1, Order::count());
    }

    public function test_discount_cannot_exceed_subtotal(): void
    {
        $payload = [
            'lead_id' => $this->leadWithAttribution->id,
            'discount' => 150, // subtotal is 100
            'items' => [
                ['description' => 'Service', 'quantity' => 1, 'unit_price' => 100],
            ],
        ];

        $response = $this->actingAs($this->staff)->post(route('orders.store'), $payload);
        $response->assertSessionHasErrors(['discount']);
        $this->assertEquals(0, Order::count());
    }

    public function test_order_status_transitions(): void
    {
        /** @var Order $order */
        $order = Order::factory()->create([
            'lead_id' => $this->leadWithAttribution->id,
            'customer_id' => $this->leadWithAttribution->customer_id,
            'referral_attribution_id' => $this->attribution->id,
            'status' => OrderStatus::PENDING,
        ]);

        // PENDING -> CONFIRMED
        $this->actingAs($this->admin)->patch(route('orders.confirm', $order))->assertRedirect();
        $this->assertEquals(OrderStatus::CONFIRMED, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->confirmed_at);

        // CONFIRMED -> COMPLETED
        $this->actingAs($this->admin)->patch(route('orders.complete', $order))->assertRedirect();
        $this->assertEquals(OrderStatus::COMPLETED, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->completed_at);

        // Attempt invalid: COMPLETED -> CANCELLED (via service or policy)
        $orderService = app(OrderService::class);
        $this->expectException(ValidationException::class);
        $orderService->cancel($order->fresh(), $this->admin, 'Attempt cancel completed');
    }

    public function test_cancelled_order_cannot_be_confirmed(): void
    {
        /** @var Order $order */
        $order = Order::factory()->create([
            'status' => OrderStatus::CANCELLED,
            'cancelled_at' => now(),
        ]);

        $orderService = app(OrderService::class);
        $this->expectException(ValidationException::class);
        $orderService->confirm($order, $this->admin);
    }

    public function test_historical_attribution_remains_intact_if_lead_attribution_changes(): void
    {
        // 1. Create order linked to attribution A
        $order = Order::factory()->create([
            'lead_id' => $this->leadWithAttribution->id,
            'customer_id' => $this->leadWithAttribution->customer_id,
            'referral_attribution_id' => $this->attribution->id,
            'status' => OrderStatus::CONFIRMED,
        ]);

        // 2. Simulate later change on Lead's referral attribution (new attribution B)
        $otherAttribution = ReferralAttribution::create([
            'referral_event_id' => $this->attribution->referral_event_id,
            'referral_code_id' => $this->attribution->referral_code_id,
            'customer_id' => $this->attribution->customer_id,
            'lead_id' => $this->attribution->lead_id,
            'referral_partner_id' => $this->attribution->referral_partner_id,
            'driver_id' => $this->attribution->driver_id,
            'vehicle_id' => $this->attribution->vehicle_id,
            'attribution_status' => ReferralAttributionStatus::ACTIVE,
            'attributed_at' => now(),
        ]);
        $this->leadWithAttribution->update([
            'referral_attribution_id' => $otherAttribution->id,
        ]);

        // 3. Order's historical referral attribution must NOT change
        $this->assertEquals($this->attribution->id, $order->fresh()->referral_attribution_id);
        $this->assertNotEquals($this->leadWithAttribution->fresh()->referral_attribution_id, $order->fresh()->referral_attribution_id);
    }

    public function test_finance_role_cannot_create_orders(): void
    {
        $payload = [
            'lead_id' => $this->leadWithAttribution->id,
            'items' => [
                ['description' => 'Service', 'quantity' => 1, 'unit_price' => 100],
            ],
        ];

        $response = $this->actingAs($this->finance)->post(route('orders.store'), $payload);
        $response->assertForbidden();
        $this->assertEquals(0, Order::count());
    }
}
