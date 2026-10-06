<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles;

use App\Enums\DriverStatus;
use App\Enums\PartnerStatus;
use App\Enums\RoleName;
use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use App\Models\Driver;
use App\Models\ReferralPartner;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;
    private User $finance;
    private ReferralPartner $partner;
    private Driver $activeDriver;
    private Driver $inactiveDriver;

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

        $this->partner = ReferralPartner::factory()->create([
            'status' => PartnerStatus::ACTIVE,
        ]);

        $this->activeDriver = Driver::factory()->create([
            'referral_partner_id' => $this->partner->id,
            'status' => DriverStatus::ACTIVE,
        ]);

        $this->inactiveDriver = Driver::factory()->create([
            'referral_partner_id' => $this->partner->id,
            'status' => DriverStatus::INACTIVE,
        ]);
    }

    public function test_authorized_user_can_view_vehicles_list(): void
    {
        Vehicle::factory()->count(3)->create(['driver_id' => $this->activeDriver->id]);

        $response = $this->actingAs($this->admin)->get(route('vehicles.index'));

        $response->assertOk();
    }

    public function test_staff_can_view_vehicles_list(): void
    {
        Vehicle::factory()->count(2)->create(['driver_id' => $this->activeDriver->id]);

        $response = $this->actingAs($this->staff)->get(route('vehicles.index'));

        $response->assertOk();
    }

    public function test_finance_cannot_view_vehicles(): void
    {
        $response = $this->actingAs($this->finance)->get(route('vehicles.index'));

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get(route('vehicles.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authorized_user_can_create_vehicle_with_normalized_plate(): void
    {
        $payload = [
            'driver_id' => $this->activeDriver->id,
            'vehicle_type' => VehicleType::TUK_TUK->value,
            'plate_number' => '  pp 1ab-2345  ',
            'make' => 'Bajaj',
            'model' => 'RE Maxima',
            'color' => 'Blue',
            'notes' => 'Has translation poster on back',
            'status' => VehicleStatus::ACTIVE->value,
        ];

        $response = $this->actingAs($this->admin)->post(route('vehicles.store'), $payload);

        $response->assertRedirect(route('vehicles.index'));
        $this->assertDatabaseHas('vehicles', [
            'plate_number' => 'PP 1AB-2345',
            'vehicle_type' => VehicleType::TUK_TUK->value,
            'driver_id' => $this->activeDriver->id,
            'status' => VehicleStatus::ACTIVE->value,
        ]);
    }

    public function test_creating_vehicle_with_inactive_driver_is_rejected(): void
    {
        $payload = [
            'driver_id' => $this->inactiveDriver->id,
            'vehicle_type' => VehicleType::TAXI->value,
            'plate_number' => 'PP 2BC-9999',
            'status' => VehicleStatus::ACTIVE->value,
        ];

        $response = $this->actingAs($this->admin)->post(route('vehicles.store'), $payload);

        $response->assertSessionHasErrors(['driver_id']);
        $this->assertDatabaseMissing('vehicles', [
            'plate_number' => 'PP 2BC-9999',
        ]);
    }

    public function test_duplicate_active_plate_number_is_prevented(): void
    {
        Vehicle::factory()->create([
            'driver_id' => $this->activeDriver->id,
            'plate_number' => 'PP 1AB-5555',
            'status' => VehicleStatus::ACTIVE,
        ]);

        $payload = [
            'driver_id' => $this->activeDriver->id,
            'vehicle_type' => VehicleType::TAXI->value,
            'plate_number' => 'pp 1ab-5555', // Case-insensitive normalized match
            'status' => VehicleStatus::ACTIVE->value,
        ];

        $response = $this->actingAs($this->admin)->post(route('vehicles.store'), $payload);

        $response->assertSessionHasErrors(['plate_number']);
    }

    public function test_staff_cannot_create_vehicle(): void
    {
        $payload = [
            'driver_id' => $this->activeDriver->id,
            'vehicle_type' => VehicleType::TUK_TUK->value,
            'plate_number' => 'PP 1XX-0000',
            'status' => VehicleStatus::ACTIVE->value,
        ];

        $response = $this->actingAs($this->staff)->post(route('vehicles.store'), $payload);

        $response->assertForbidden();
    }

    public function test_authorized_user_can_update_vehicle_and_reassign_driver(): void
    {
        $secondDriver = Driver::factory()->create([
            'referral_partner_id' => $this->partner->id,
            'status' => DriverStatus::ACTIVE,
        ]);

        $vehicle = Vehicle::factory()->create([
            'driver_id' => $this->activeDriver->id,
            'plate_number' => 'PP 1AA-1111',
            'status' => VehicleStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->admin)->put(route('vehicles.update', $vehicle), [
            'driver_id' => $secondDriver->id,
            'vehicle_type' => $vehicle->vehicle_type->value,
            'plate_number' => 'PP 1AA-1111',
            'status' => VehicleStatus::ACTIVE->value,
            'make' => 'Toyota',
            'model' => 'Prius',
        ]);

        $response->assertRedirect(route('vehicles.index'));
        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'driver_id' => $secondDriver->id,
            'model' => 'Prius',
        ]);
    }

    public function test_authorized_user_can_deactivate_and_activate_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create([
            'driver_id' => $this->activeDriver->id,
            'status' => VehicleStatus::ACTIVE,
        ]);

        // Deactivate
        $response = $this->actingAs($this->admin)->patch(route('vehicles.deactivate', $vehicle));
        $response->assertRedirect();
        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'status' => VehicleStatus::INACTIVE->value,
        ]);

        // Activate
        $response = $this->actingAs($this->admin)->patch(route('vehicles.activate', $vehicle));
        $response->assertRedirect();
        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'status' => VehicleStatus::ACTIVE->value,
        ]);
    }

    public function test_historical_safety_deactivated_vehicle_remains_intact_in_database(): void
    {
        $vehicle = Vehicle::factory()->create([
            'driver_id' => $this->activeDriver->id,
            'plate_number' => 'HISTORICAL-999',
            'status' => VehicleStatus::ACTIVE,
        ]);

        $this->actingAs($this->admin)->patch(route('vehicles.deactivate', $vehicle));

        $freshVehicle = $vehicle->fresh();
        $this->assertNotNull($freshVehicle);
        $this->assertEquals(VehicleStatus::INACTIVE, $freshVehicle->status);
        $this->assertEquals($this->activeDriver->id, $freshVehicle->driver_id);
    }
}
