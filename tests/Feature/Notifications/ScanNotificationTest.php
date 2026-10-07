<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Enums\DriverStatus;
use App\Enums\PartnerStatus;
use App\Enums\ReferralCodeStatus;
use App\Enums\RoleName;
use App\Enums\TelegramEventType;
use App\Enums\VehicleStatus;
use App\Models\Driver;
use App\Models\ReferralCode;
use App\Models\ReferralEvent;
use App\Models\ReferralPartner;
use App\Models\TelegramUser;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ReferralPartner $partner;
    private Driver $driver;
    private Vehicle $vehicle;
    private ReferralCode $referralCode;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole(RoleName::SUPER_ADMIN->value);

        $this->partner = ReferralPartner::create([
            'name' => 'Metro Tuk-Tuk Association',
            'type' => 'COMPANY',
            'phone' => '+85512345678',
            'status' => PartnerStatus::ACTIVE,
            'commission_rate' => 10.0,
        ]);

        $this->driver = Driver::create([
            'referral_partner_id' => $this->partner->id,
            'name' => 'Sok Dara',
            'phone' => '+85598765432',
            'status' => DriverStatus::ACTIVE,
        ]);

        $this->vehicle = Vehicle::create([
            'driver_id' => $this->driver->id,
            'plate_number' => 'PP-1AA-1234',
            'vehicle_type' => 'TUK_TUK',
            'status' => VehicleStatus::ACTIVE,
        ]);

        $this->referralCode = ReferralCode::create([
            'referral_partner_id' => $this->partner->id,
            'driver_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'code' => 'REF_TK999',
            'status' => ReferralCodeStatus::ACTIVE,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_guest_cannot_access_scan_notifications(): void
    {
        $response = $this->getJson(route('notifications.scans'));
        $response->assertUnauthorized();
    }

    public function test_authenticated_user_receives_recent_scan_notifications(): void
    {
        $telegramUser = TelegramUser::create([
            'telegram_id' => 999888777,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'username' => 'johndoe',
            'last_interaction_at' => now(),
        ]);

        $event = ReferralEvent::create([
            'referral_code_id' => $this->referralCode->id,
            'telegram_user_id' => $telegramUser->id,
            'event_type' => TelegramEventType::SCAN,
            'event_token' => 'REF_TK999',
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->getJson(route('notifications.scans'));

        $response->assertOk();
        $response->assertJsonStructure([
            'scans' => [
                '*' => [
                    'id',
                    'event_type',
                    'is_repeat',
                    'type_badge',
                    'customer_name',
                    'customer_username',
                    'driver_name',
                    'vehicle_plate',
                    'referral_code',
                    'time_ago',
                ],
            ],
            'latest_id',
            'total_recent',
        ]);

        $response->assertJsonFragment([
            'id' => $event->id,
            'referral_code' => 'REF_TK999',
            'customer_name' => 'John Doe',
            'driver_name' => 'Sok Dara',
            'vehicle_plate' => 'PP-1AA-1234',
        ]);
    }

    public function test_since_id_filtering_detects_new_scan_events(): void
    {
        $telegramUser = TelegramUser::create([
            'telegram_id' => 888777666,
            'first_name' => 'Alice',
            'username' => 'alice_cambodia',
            'last_interaction_at' => now(),
        ]);

        // Event 1 (old)
        $event1 = ReferralEvent::create([
            'referral_code_id' => $this->referralCode->id,
            'telegram_user_id' => $telegramUser->id,
            'event_type' => TelegramEventType::SCAN,
            'event_token' => 'REF_TK999',
            'occurred_at' => now()->subMinutes(10),
        ]);

        // Event 2 (new)
        $event2 = ReferralEvent::create([
            'referral_code_id' => $this->referralCode->id,
            'telegram_user_id' => $telegramUser->id,
            'event_type' => TelegramEventType::REPEAT_SCAN,
            'event_token' => 'REF_TK999',
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->getJson(route('notifications.scans', [
            'since_id' => $event1->id,
        ]));

        $response->assertOk();
        $response->assertJson([
            'has_new' => true,
            'latest_id' => $event2->id,
        ]);

        $this->assertCount(1, $response->json('new_scans'));
        $this->assertEquals($event2->id, $response->json('new_scans.0.id'));
        $this->assertEquals('Repeat Scan', $response->json('new_scans.0.type_badge'));
    }

    public function test_artisan_simulate_scan_command_generates_notification(): void
    {
        $this->artisan('telegram:simulate-scan', [
            'code' => 'REF_TK999',
            '--name' => 'David Miller',
            '--username' => 'davidm',
        ])->assertSuccessful();

        $response = $this->actingAs($this->user)->getJson(route('notifications.scans'));

        $response->assertOk();
        $this->assertGreaterThan(0, $response->json('total_recent'));

        $firstScan = $response->json('scans.0');
        $this->assertEquals('REF_TK999', $firstScan['referral_code']);
        $this->assertEquals('David Miller', $firstScan['customer_name']);
        $this->assertEquals('davidm', $firstScan['customer_username']);
        $this->assertEquals('Sok Dara', $firstScan['driver_name']);
    }
}
