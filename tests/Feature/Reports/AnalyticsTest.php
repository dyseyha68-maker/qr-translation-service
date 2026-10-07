<?php

declare(strict_types=1);

namespace Tests\Feature\Reports;

use App\Enums\CommissionStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RoleName;
use App\Models\Commission;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ReferralPartner;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_super_admin_and_finance_can_access_performance_reports(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(RoleName::SUPER_ADMIN->value);

        $finance = User::factory()->create();
        $finance->assignRole(RoleName::FINANCE->value);

        $responseAdmin = $this->actingAs($superAdmin)->get(route('reports.performance'));
        $responseAdmin->assertOk();

        $responseFinance = $this->actingAs($finance)->get(route('reports.performance'));
        $responseFinance->assertOk();
    }

    public function test_staff_without_permission_cannot_access_performance_reports(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole(RoleName::STAFF->value);

        $response = $this->actingAs($staff)->get(route('reports.performance'));
        $response->assertForbidden();
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get(route('reports.performance'));
        $response->assertRedirect(route('login'));
    }

    public function test_performance_report_aggregates_kpis_accurately(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(RoleName::SUPER_ADMIN->value);

        $partner = ReferralPartner::factory()->create(['name' => 'Metro Tuk-Tuk Co']);
        $driver = Driver::factory()->create([
            'referral_partner_id' => $partner->id,
            'name' => 'Sokha Mean',
        ]);

        $customer = Customer::factory()->create();

        $attribution = \App\Models\ReferralAttribution::factory()->create([
            'driver_id' => $driver->id,
            'referral_partner_id' => $partner->id,
        ]);

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'referral_attribution_id' => $attribution->id,
            'status' => OrderStatus::COMPLETED->value,
            'total_amount' => '100.00',
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => '100.00',
            'status' => \App\Enums\PaymentRecordStatus::PAID->value,
            'paid_at' => now(),
        ]);

        Commission::factory()->create([
            'order_id' => $order->id,
            'driver_id' => $driver->id,
            'referral_attribution_id' => $attribution->id,
            'commission_amount' => '10.00',
            'status' => CommissionStatus::PAID->value,
            'earned_at' => now(),
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($superAdmin)->get(route('reports.performance'));
        $response->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Performance')
            ->has('summary')
            ->where('summary.total_orders', 1)
            ->where('summary.total_revenue', '100.00')
            ->where('summary.total_commissions_paid', '10.00')
            ->has('driverPerformance')
            ->has('partnerPerformance')
        );
    }

    public function test_export_drivers_csv_returns_stream_with_valid_headers(): void
    {
        $finance = User::factory()->create();
        $finance->assignRole(RoleName::FINANCE->value);

        $partner = ReferralPartner::factory()->create();
        Driver::factory()->create([
            'referral_partner_id' => $partner->id,
            'name' => 'Vannak Heng',
        ]);

        $response = $this->actingAs($finance)->get(route('reports.export.drivers'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment; filename=driver-performance-', (string) $response->headers->get('content-disposition'));
    }

    public function test_export_commissions_csv_returns_stream_with_valid_headers(): void
    {
        $finance = User::factory()->create();
        $finance->assignRole(RoleName::FINANCE->value);

        $response = $this->actingAs($finance)->get(route('reports.export.commissions'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment; filename=commissions-ledger-', (string) $response->headers->get('content-disposition'));
    }

    public function test_export_orders_csv_returns_stream_with_valid_headers(): void
    {
        $finance = User::factory()->create();
        $finance->assignRole(RoleName::FINANCE->value);

        $response = $this->actingAs($finance)->get(route('reports.export.orders'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment; filename=orders-fulfillment-', (string) $response->headers->get('content-disposition'));
    }

    public function test_health_check_endpoint_is_accessible(): void
    {
        $response = $this->get('/up');
        $response->assertOk();
    }
}
