<?php

declare(strict_types=1);

namespace Tests\Feature\Drivers;

use App\Enums\DriverStatus;
use App\Enums\PartnerStatus;
use App\Enums\RoleName;
use App\Models\Driver;
use App\Models\ReferralPartner;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;
    private User $finance;
    private ReferralPartner $activePartner;
    private ReferralPartner $inactivePartner;

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

        $this->activePartner = ReferralPartner::factory()->create([
            'status' => PartnerStatus::ACTIVE,
        ]);

        $this->inactivePartner = ReferralPartner::factory()->create([
            'status' => PartnerStatus::INACTIVE,
        ]);
    }

    public function test_authorized_user_can_view_drivers_list(): void
    {
        Driver::factory()->count(3)->create(['referral_partner_id' => $this->activePartner->id]);

        $response = $this->actingAs($this->admin)->get(route('drivers.index'));

        $response->assertOk();
    }

    public function test_staff_can_view_drivers_list(): void
    {
        Driver::factory()->count(2)->create(['referral_partner_id' => $this->activePartner->id]);

        $response = $this->actingAs($this->staff)->get(route('drivers.index'));

        $response->assertOk();
    }

    public function test_finance_cannot_view_drivers(): void
    {
        $response = $this->actingAs($this->finance)->get(route('drivers.index'));

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get(route('drivers.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authorized_user_can_create_driver(): void
    {
        $payload = [
            'referral_partner_id' => $this->activePartner->id,
            'name' => 'Chhim Sokha',
            'phone' => '+855 12 777 888',
            'email' => 'sokha@gmail.com',
            'national_id' => '020304050',
            'address' => 'Sangkat Boeung Keng Kang, Phnom Penh',
            'notes' => 'Airport transfer specialist',
            'status' => DriverStatus::ACTIVE->value,
        ];

        $response = $this->actingAs($this->admin)->post(route('drivers.store'), $payload);

        $response->assertRedirect(route('drivers.index'));
        $this->assertDatabaseHas('drivers', [
            'name' => 'Chhim Sokha',
            'referral_partner_id' => $this->activePartner->id,
            'national_id' => '020304050',
            'status' => DriverStatus::ACTIVE->value,
        ]);
    }

    public function test_creating_driver_under_inactive_partner_is_rejected(): void
    {
        $payload = [
            'referral_partner_id' => $this->inactivePartner->id,
            'name' => 'Inactive Partner Driver',
            'status' => DriverStatus::ACTIVE->value,
        ];

        $response = $this->actingAs($this->admin)->post(route('drivers.store'), $payload);

        $response->assertSessionHasErrors(['referral_partner_id']);
        $this->assertDatabaseMissing('drivers', [
            'name' => 'Inactive Partner Driver',
        ]);
    }

    public function test_staff_cannot_create_driver(): void
    {
        $payload = [
            'referral_partner_id' => $this->activePartner->id,
            'name' => 'Unauthorized Driver',
            'status' => DriverStatus::ACTIVE->value,
        ];

        $response = $this->actingAs($this->staff)->post(route('drivers.store'), $payload);

        $response->assertForbidden();
    }

    public function test_validation_requires_driver_name_and_partner_and_status(): void
    {
        $response = $this->actingAs($this->admin)->post(route('drivers.store'), []);

        $response->assertSessionHasErrors(['name', 'referral_partner_id', 'status']);
    }

    public function test_authorized_user_can_update_driver(): void
    {
        $driver = Driver::factory()->create([
            'referral_partner_id' => $this->activePartner->id,
            'name' => 'Original Driver Name',
            'phone' => '+855 12 111 222',
        ]);

        $response = $this->actingAs($this->admin)->put(route('drivers.update', $driver), [
            'referral_partner_id' => $this->activePartner->id,
            'name' => 'Updated Driver Name',
            'phone' => '+855 12 999 888',
            'status' => $driver->status->value,
        ]);

        $response->assertRedirect(route('drivers.index'));
        $this->assertDatabaseHas('drivers', [
            'id' => $driver->id,
            'name' => 'Updated Driver Name',
            'phone' => '+855 12 999 888',
        ]);
    }

    public function test_authorized_user_can_deactivate_and_activate_driver(): void
    {
        $driver = Driver::factory()->create([
            'referral_partner_id' => $this->activePartner->id,
            'status' => DriverStatus::ACTIVE,
        ]);

        // Deactivate
        $response = $this->actingAs($this->admin)->patch(route('drivers.deactivate', $driver));
        $response->assertRedirect();
        $this->assertDatabaseHas('drivers', [
            'id' => $driver->id,
            'status' => DriverStatus::INACTIVE->value,
        ]);

        // Activate
        $response = $this->actingAs($this->admin)->patch(route('drivers.activate', $driver));
        $response->assertRedirect();
        $this->assertDatabaseHas('drivers', [
            'id' => $driver->id,
            'status' => DriverStatus::ACTIVE->value,
        ]);
    }

    public function test_driver_with_vehicles_cannot_be_deleted(): void
    {
        $driver = Driver::factory()->create(['referral_partner_id' => $this->activePartner->id]);
        Vehicle::factory()->create(['driver_id' => $driver->id]);

        $response = $this->actingAs($this->admin)->delete(route('drivers.destroy', $driver));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('drivers', ['id' => $driver->id]);
    }

    public function test_driver_without_vehicles_can_be_deleted(): void
    {
        $driver = Driver::factory()->create(['referral_partner_id' => $this->activePartner->id]);

        $response = $this->actingAs($this->admin)->delete(route('drivers.destroy', $driver));

        $response->assertRedirect(route('drivers.index'));
        $this->assertDatabaseMissing('drivers', ['id' => $driver->id]);
    }

    public function test_deactivation_preserves_driver_and_partner_relationship(): void
    {
        $driver = Driver::factory()->create([
            'referral_partner_id' => $this->activePartner->id,
            'status' => DriverStatus::ACTIVE,
        ]);

        $this->actingAs($this->admin)->patch(route('drivers.deactivate', $driver));

        $freshDriver = $driver->fresh();
        $this->assertNotNull($freshDriver);
        $this->assertEquals(DriverStatus::INACTIVE, $freshDriver->status);
        $this->assertEquals($this->activePartner->id, $freshDriver->referral_partner_id);
    }
}
