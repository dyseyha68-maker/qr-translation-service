<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CommissionStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentRecordStatus;
use App\Enums\RoleName;
use App\Models\Commission;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ReferralAttribution;
use App\Models\ReferralCode;
use App\Models\ReferralEvent;
use App\Models\ReferralPartner;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_receives_complete_chart_and_trend_payload(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::SUPER_ADMIN->value);

        $partner = ReferralPartner::factory()->create(['name' => 'Angkor Tuk-Tuk Alliance']);
        $driver = Driver::factory()->create([
            'referral_partner_id' => $partner->id,
            'name' => 'Dara Sam',
        ]);
        $vehicle = Vehicle::factory()->create([
            'driver_id' => $driver->id,
            'plate_number' => '1AB-1234',
        ]);
        $code = ReferralCode::factory()->create([
            'referral_partner_id' => $partner->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'code' => 'REF_TEST123',
        ]);

        $event = ReferralEvent::factory()->create([
            'referral_code_id' => $code->id,
            'event_type' => 'SCAN',
            'occurred_at' => now(),
        ]);

        $customer = Customer::factory()->create();
        $attribution = ReferralAttribution::factory()->create([
            'referral_event_id' => $event->id,
            'referral_code_id' => $code->id,
            'customer_id' => $customer->id,
            'referral_partner_id' => $partner->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $lead = Lead::factory()->create([
            'customer_id' => $customer->id,
            'referral_attribution_id' => $attribution->id,
        ]);

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'lead_id' => $lead->id,
            'referral_attribution_id' => $attribution->id,
            'status' => OrderStatus::COMPLETED->value,
            'total_amount' => '150.00',
            'confirmed_at' => now(),
            'completed_at' => now(),
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => '150.00',
            'status' => PaymentRecordStatus::PAID->value,
            'payment_method' => 'khqr_manual',
            'paid_at' => now(),
        ]);

        Commission::factory()->create([
            'order_id' => $order->id,
            'referral_attribution_id' => $attribution->id,
            'referral_partner_id' => $partner->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'commission_amount' => '15.00',
            'status' => CommissionStatus::PAID->value,
            'earned_at' => now(),
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('trends')
            ->has('trends.labels')
            ->has('trends.scans')
            ->has('trends.leads')
            ->has('trends.revenue')
            ->has('trends.commissions')
            ->where('trends.days', 14)
            ->has('funnel')
            ->where('funnel.scans', 1)
            ->where('funnel.leads', 1)
            ->where('funnel.orders', 1)
            ->where('funnel.paid_orders', 1)
            ->has('orderDistribution')
            ->has('paymentDistribution')
            ->has('paymentMethodDistribution')
            ->has('topDrivers')
            ->has('financialMargin')
            ->where('financialMargin.gross_revenue', 150)
            ->where('financialMargin.driver_commissions', 15)
            ->where('financialMargin.net_revenue', 135)
        );
    }

    public function test_dashboard_supports_custom_days_filtering(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::STAFF->value);

        $response7 = $this->actingAs($user)->get(route('dashboard', ['days' => 7]));
        $response7->assertOk();
        $response7->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('trends.days', 7)
            ->where('selectedDays', 7)
        );

        $response30 = $this->actingAs($user)->get(route('dashboard', ['days' => 30]));
        $response30->assertOk();
        $response30->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('trends.days', 30)
            ->where('selectedDays', 30)
        );
    }
}
