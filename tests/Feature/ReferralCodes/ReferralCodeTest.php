<?php

declare(strict_types=1);

namespace Tests\Feature\ReferralCodes;

use App\Enums\DriverStatus;
use App\Enums\PartnerStatus;
use App\Enums\ReferralCodeStatus;
use App\Enums\RoleName;
use App\Enums\VehicleStatus;
use App\Models\Driver;
use App\Models\ReferralCode;
use App\Models\ReferralPartner;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\TelegramReferralLinkService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralCodeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;
    private User $finance;
    private ReferralPartner $partner;
    private Driver $driver;
    private Vehicle $vehicle;

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

        $this->driver = Driver::factory()->create([
            'referral_partner_id' => $this->partner->id,
            'status' => DriverStatus::ACTIVE,
        ]);

        $this->vehicle = Vehicle::factory()->create([
            'driver_id' => $this->driver->id,
            'status' => VehicleStatus::ACTIVE,
        ]);
    }

    public function test_authorized_admin_can_view_referral_codes_list(): void
    {
        ReferralCode::factory()->create([
            'referral_partner_id' => $this->partner->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('referral-codes.index'));

        $response->assertOk();
    }

    public function test_staff_can_view_referral_codes_list(): void
    {
        $response = $this->actingAs($this->staff)->get(route('referral-codes.index'));

        $response->assertOk();
    }

    public function test_finance_cannot_view_referral_codes(): void
    {
        $response = $this->actingAs($this->finance)->get(route('referral-codes.index'));

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get(route('referral-codes.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authorized_admin_can_create_referral_code(): void
    {
        $payload = [
            'referral_partner_id' => $this->partner->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'code' => 'REF-TK999',
            'label' => 'Backseat Sticker',
            'notes' => 'Airport transfer tuk-tuk',
            'status' => ReferralCodeStatus::ACTIVE->value,
        ];

        $response = $this->actingAs($this->admin)->post(route('referral-codes.store'), $payload);

        $code = ReferralCode::where('code', 'REF-TK999')->first();
        $this->assertNotNull($code);
        $response->assertRedirect(route('referral-codes.show', $code->id));

        $this->assertDatabaseHas('referral_codes', [
            'code' => 'REF-TK999',
            'referral_partner_id' => $this->partner->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'created_by' => $this->admin->id,
            'status' => ReferralCodeStatus::ACTIVE->value,
        ]);
    }

    public function test_staff_cannot_create_referral_code(): void
    {
        $payload = [
            'referral_partner_id' => $this->partner->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'code' => 'REF-STAFF01',
        ];

        $response = $this->actingAs($this->staff)->post(route('referral-codes.store'), $payload);

        $response->assertForbidden();
        $this->assertDatabaseMissing('referral_codes', ['code' => 'REF-STAFF01']);
    }

    public function test_creating_referral_code_with_inactive_partner_is_rejected(): void
    {
        $inactivePartner = ReferralPartner::factory()->create(['status' => PartnerStatus::INACTIVE]);
        $driver = Driver::factory()->create([
            'referral_partner_id' => $inactivePartner->id,
            'status' => DriverStatus::ACTIVE,
        ]);
        $vehicle = Vehicle::factory()->create([
            'driver_id' => $driver->id,
            'status' => VehicleStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->admin)->post(route('referral-codes.store'), [
            'referral_partner_id' => $inactivePartner->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $response->assertSessionHasErrors(['referral_partner_id']);
    }

    public function test_creating_referral_code_with_inactive_driver_is_rejected(): void
    {
        $inactiveDriver = Driver::factory()->create([
            'referral_partner_id' => $this->partner->id,
            'status' => DriverStatus::INACTIVE,
        ]);
        $vehicle = Vehicle::factory()->create([
            'driver_id' => $inactiveDriver->id,
            'status' => VehicleStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->admin)->post(route('referral-codes.store'), [
            'referral_partner_id' => $this->partner->id,
            'driver_id' => $inactiveDriver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $response->assertSessionHasErrors(['driver_id']);
    }

    public function test_creating_referral_code_with_inactive_vehicle_is_rejected(): void
    {
        $inactiveVehicle = Vehicle::factory()->create([
            'driver_id' => $this->driver->id,
            'status' => VehicleStatus::INACTIVE,
        ]);

        $response = $this->actingAs($this->admin)->post(route('referral-codes.store'), [
            'referral_partner_id' => $this->partner->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $inactiveVehicle->id,
        ]);

        $response->assertSessionHasErrors(['vehicle_id']);
    }

    public function test_driver_belonging_to_another_partner_is_rejected(): void
    {
        $anotherPartner = ReferralPartner::factory()->create(['status' => PartnerStatus::ACTIVE]);
        // $this->driver belongs to $this->partner, not $anotherPartner

        $response = $this->actingAs($this->admin)->post(route('referral-codes.store'), [
            'referral_partner_id' => $anotherPartner->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
        ]);

        $response->assertSessionHasErrors(['driver_id']);
    }

    public function test_vehicle_belonging_to_another_driver_is_rejected(): void
    {
        $anotherDriver = Driver::factory()->create([
            'referral_partner_id' => $this->partner->id,
            'status' => DriverStatus::ACTIVE,
        ]);
        // $this->vehicle belongs to $this->driver, not $anotherDriver

        $response = $this->actingAs($this->admin)->post(route('referral-codes.store'), [
            'referral_partner_id' => $this->partner->id,
            'driver_id' => $anotherDriver->id,
            'vehicle_id' => $this->vehicle->id,
        ]);

        $response->assertSessionHasErrors(['vehicle_id']);
    }

    public function test_duplicate_code_is_rejected(): void
    {
        ReferralCode::factory()->create([
            'referral_partner_id' => $this->partner->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'code' => 'REF-EXISTING',
        ]);

        $response = $this->actingAs($this->admin)->post(route('referral-codes.store'), [
            'referral_partner_id' => $this->partner->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'code' => 'ref-existing', // Case-insensitive normalized collision
        ]);

        $response->assertSessionHasErrors(['code']);
    }

    public function test_authorized_user_can_view_referral_code_detail(): void
    {
        $code = ReferralCode::factory()->create([
            'referral_partner_id' => $this->partner->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('referral-codes.show', $code->id));

        $response->assertOk();
    }

    public function test_authorized_user_can_update_metadata(): void
    {
        $code = ReferralCode::factory()->create([
            'referral_partner_id' => $this->partner->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'label' => 'Original Label',
            'notes' => 'Original Notes',
        ]);

        $response = $this->actingAs($this->admin)->put(route('referral-codes.update', $code->id), [
            'label' => 'Updated Label',
            'notes' => 'Updated Notes',
        ]);

        $response->assertRedirect(route('referral-codes.show', $code->id));
        $this->assertDatabaseHas('referral_codes', [
            'id' => $code->id,
            'label' => 'Updated Label',
            'notes' => 'Updated Notes',
            // Bound hierarchy remains completely unchanged
            'referral_partner_id' => $this->partner->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'code' => $code->code,
        ]);
    }

    public function test_authorized_user_can_deactivate_and_activate_code(): void
    {
        $code = ReferralCode::factory()->create([
            'referral_partner_id' => $this->partner->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => ReferralCodeStatus::ACTIVE,
        ]);

        // Deactivate
        $response = $this->actingAs($this->admin)->patch(route('referral-codes.deactivate', $code->id));
        $response->assertRedirect();
        $this->assertDatabaseHas('referral_codes', [
            'id' => $code->id,
            'status' => ReferralCodeStatus::INACTIVE->value,
        ]);
        $this->assertNotNull($code->fresh()->deactivated_at);

        // Activate
        $response = $this->actingAs($this->admin)->patch(route('referral-codes.activate', $code->id));
        $response->assertRedirect();
        $this->assertDatabaseHas('referral_codes', [
            'id' => $code->id,
            'status' => ReferralCodeStatus::ACTIVE->value,
        ]);
        $this->assertNull($code->fresh()->deactivated_at);
    }

    public function test_telegram_deep_link_generation_matches_specification(): void
    {
        $code = ReferralCode::factory()->create([
            'referral_partner_id' => $this->partner->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'code' => 'REF-TK100',
        ]);

        /** @var TelegramReferralLinkService $linkService */
        $linkService = app(TelegramReferralLinkService::class);
        $url = $linkService->buildForReferralCode($code);

        $bot = config('services.telegram.bot_username');
        $this->assertEquals("https://t.me/{$bot}?start=REF-TK100", $url);
    }

    public function test_qr_endpoint_returns_valid_svg_image(): void
    {
        $code = ReferralCode::factory()->create([
            'referral_partner_id' => $this->partner->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
        ]);

        $response = $this->actingAs($this->staff)->get(route('referral-codes.qr', $code->id));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertStringContainsString('<svg', $response->getContent());
    }

    public function test_download_endpoint_returns_attachment_response(): void
    {
        $code = ReferralCode::factory()->create([
            'referral_partner_id' => $this->partner->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'code' => 'REF-DL01',
        ]);

        $response = $this->actingAs($this->staff)->get(route('referral-codes.download', $code->id));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertStringContainsString('attachment; filename="qr-REF-DL01.svg"', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_print_page_returns_ok_for_authorized_staff(): void
    {
        $code = ReferralCode::factory()->create([
            'referral_partner_id' => $this->partner->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
        ]);

        $response = $this->actingAs($this->staff)->get(route('referral-codes.print', $code->id));

        $response->assertOk();
    }
}
