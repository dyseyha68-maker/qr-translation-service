<?php

declare(strict_types=1);

namespace Tests\Feature\Telegram;

use App\Enums\DriverStatus;
use App\Enums\LeadStatus;
use App\Enums\PartnerStatus;
use App\Enums\ReferralAttributionStatus;
use App\Enums\ReferralCodeStatus;
use App\Enums\RoleName;
use App\Enums\TelegramEventType;
use App\Enums\VehicleStatus;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Lead;
use App\Models\ReferralAttribution;
use App\Models\ReferralCode;
use App\Models\ReferralEvent;
use App\Models\ReferralPartner;
use App\Models\TelegramUser;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    private ReferralPartner $partnerA;
    private Driver $driverA;
    private Vehicle $vehicleA;
    private ReferralCode $codeA;

    private ReferralPartner $partnerB;
    private Driver $driverB;
    private Vehicle $vehicleB;
    private ReferralCode $codeB;

    private User $admin;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        Config::set('services.telegram.webhook_secret', 'secret-test-token-12345');
        Config::set('services.telegram.bot_token', ''); // Empty to avoid outbound network calls

        $this->admin = User::factory()->create();
        $this->admin->assignRole(RoleName::ADMIN->value);

        $this->staff = User::factory()->create();
        $this->staff->assignRole(RoleName::STAFF->value);

        // Partner & Driver A
        $this->partnerA = ReferralPartner::factory()->create(['status' => PartnerStatus::ACTIVE]);
        $this->driverA = Driver::factory()->create([
            'referral_partner_id' => $this->partnerA->id,
            'name' => 'Driver Sokha',
            'status' => DriverStatus::ACTIVE,
        ]);
        $this->vehicleA = Vehicle::factory()->create([
            'driver_id' => $this->driverA->id,
            'plate_number' => 'PP-1AA-1111',
            'status' => VehicleStatus::ACTIVE,
        ]);
        $this->codeA = ReferralCode::factory()->create([
            'code' => 'REF_SOKHA01',
            'referral_partner_id' => $this->partnerA->id,
            'driver_id' => $this->driverA->id,
            'vehicle_id' => $this->vehicleA->id,
            'status' => ReferralCodeStatus::ACTIVE,
        ]);

        // Partner & Driver B
        $this->partnerB = ReferralPartner::factory()->create(['status' => PartnerStatus::ACTIVE]);
        $this->driverB = Driver::factory()->create([
            'referral_partner_id' => $this->partnerB->id,
            'name' => 'Driver Visal',
            'status' => DriverStatus::ACTIVE,
        ]);
        $this->vehicleB = Vehicle::factory()->create([
            'driver_id' => $this->driverB->id,
            'plate_number' => 'PP-1BB-2222',
            'status' => VehicleStatus::ACTIVE,
        ]);
        $this->codeB = ReferralCode::factory()->create([
            'code' => 'REF_VISAL02',
            'referral_partner_id' => $this->partnerB->id,
            'driver_id' => $this->driverB->id,
            'vehicle_id' => $this->vehicleB->id,
            'status' => ReferralCodeStatus::ACTIVE,
        ]);
    }

    private function buildTelegramPayload(int $updateId, int $telegramId, string $text, string $username = 'john_doe'): array
    {
        return [
            'update_id' => $updateId,
            'message' => [
                'message_id' => 101,
                'from' => [
                    'id' => $telegramId,
                    'is_bot' => false,
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'username' => $username,
                    'language_code' => 'en',
                ],
                'chat' => [
                    'id' => $telegramId,
                    'type' => 'private',
                ],
                'date' => 1760000000,
                'text' => $text,
            ],
        ];
    }

    public function test_webhook_rejects_missing_or_invalid_secret_token(): void
    {
        $payload = $this->buildTelegramPayload(1, 999111, '/start REF_SOKHA01');

        // Missing secret
        $response = $this->postJson(route('telegram.webhook'), $payload);
        $response->assertStatus(401);

        // Wrong secret
        $response = $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'wrong-token')
            ->postJson(route('telegram.webhook'), $payload);
        $response->assertStatus(401);
    }

    public function test_mandatory_scenario_1_first_qualified_referral_wins(): void
    {
        $telegramId = 888777111;

        // 1. Telegram user scans Driver A's QR: /start REF_SOKHA01
        $payload1 = $this->buildTelegramPayload(1001, $telegramId, '/start REF_SOKHA01', 'sreymom');

        $response1 = $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'secret-test-token-12345')
            ->postJson(route('telegram.webhook'), $payload1);

        $response1->assertOk()
            ->assertJsonPath('result.outcome', 'attributed_new_lead');

        // Verify Telegram User
        $this->assertDatabaseHas('telegram_users', [
            'telegram_id' => $telegramId,
            'username' => 'sreymom',
        ]);
        $telegramUser = TelegramUser::where('telegram_id', $telegramId)->firstOrFail();

        // Verify Customer
        $this->assertDatabaseHas('customers', [
            'telegram_user_id' => $telegramUser->id,
            'name' => 'John Doe',
        ]);
        $customer = Customer::where('telegram_user_id', $telegramUser->id)->firstOrFail();

        // Verify Lead
        $this->assertDatabaseHas('leads', [
            'customer_id' => $customer->id,
            'status' => LeadStatus::NEW->value,
        ]);
        $lead = Lead::where('customer_id', $customer->id)->firstOrFail();

        // Verify Attribution to Driver A
        $this->assertDatabaseHas('referral_attributions', [
            'lead_id' => $lead->id,
            'referral_code_id' => $this->codeA->id,
            'driver_id' => $this->driverA->id,
            'vehicle_id' => $this->vehicleA->id,
            'attribution_status' => ReferralAttributionStatus::ACTIVE->value,
        ]);

        // 2. Same user scans the exact same QR again
        $payloadRepeat = $this->buildTelegramPayload(1002, $telegramId, '/start REF_SOKHA01', 'sreymom');

        $responseRepeat = $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'secret-test-token-12345')
            ->postJson(route('telegram.webhook'), $payloadRepeat);

        $responseRepeat->assertOk()
            ->assertJsonPath('result.outcome', 'repeat_scan_preserved');

        // Assert NO duplicate active lead was created
        $this->assertEquals(1, Lead::where('customer_id', $customer->id)->count());

        // 3. Same user scans Driver B's QR code while lead is active
        $payloadDriverB = $this->buildTelegramPayload(1003, $telegramId, '/start REF_VISAL02', 'sreymom');

        $responseDriverB = $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'secret-test-token-12345')
            ->postJson(route('telegram.webhook'), $payloadDriverB);

        $responseDriverB->assertOk()
            ->assertJsonPath('result.outcome', 'repeat_scan_preserved');

        // FIRST QUALIFIED REFERRAL WINS: Active attribution remains Driver A!
        $lead->refresh();
        $this->assertEquals($this->driverA->id, $lead->referralAttribution?->driver_id);
        $this->assertEquals($this->codeA->id, $lead->referralAttribution?->referral_code_id);
        $this->assertEquals(1, Lead::where('customer_id', $customer->id)->count());

        // Staff can see original attribution
        $this->actingAs($this->staff)
            ->get(route('leads.show', $lead->id))
            ->assertOk();
    }

    public function test_mandatory_scenario_2_subsequent_lead_after_closed_lead(): void
    {
        $telegramId = 888777222;

        // 1. Initial scan with Code A
        $payload1 = $this->buildTelegramPayload(2001, $telegramId, '/start REF_SOKHA01');
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'secret-test-token-12345')
            ->postJson(route('telegram.webhook'), $payload1);

        $lead1 = Lead::firstOrFail();
        $this->assertEquals($this->driverA->id, $lead1->referralAttribution?->driver_id);

        // 2. Staff resolves/closes lead 1
        $lead1->update(['status' => LeadStatus::CONFIRMED]);

        // 3. Customer starts a genuinely new translation inquiry later with Driver B
        $payload2 = $this->buildTelegramPayload(2002, $telegramId, '/start REF_VISAL02');
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'secret-test-token-12345')
            ->postJson(route('telegram.webhook'), $payload2)
            ->assertOk()
            ->assertJsonPath('result.outcome', 'attributed_new_lead');

        // Verify two separate leads exist
        $this->assertEquals(2, Lead::count());
        $lead2 = Lead::where('id', '!=', $lead1->id)->firstOrFail();

        // Lead 1 remains attributed to Driver A
        $lead1->refresh();
        $this->assertEquals($this->driverA->id, $lead1->referralAttribution?->driver_id);

        // Lead 2 is attributed to Driver B
        $this->assertEquals($this->driverB->id, $lead2->referralAttribution?->driver_id);
        $this->assertEquals($this->codeB->id, $lead2->referralAttribution?->referral_code_id);
    }

    public function test_mandatory_scenario_3_inactive_referral_code_preserves_history_and_rejects_new_leads(): void
    {
        $telegramId1 = 888777333;
        $telegramId2 = 888777444;

        // 1. User 1 scans Code A while active
        $payload1 = $this->buildTelegramPayload(3001, $telegramId1, '/start REF_SOKHA01');
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'secret-test-token-12345')
            ->postJson(route('telegram.webhook'), $payload1);

        $lead1 = Lead::firstOrFail();
        $this->assertEquals($this->codeA->id, $lead1->referralAttribution?->referral_code_id);

        // 2. Code A is deactivated
        $this->codeA->update([
            'status' => ReferralCodeStatus::INACTIVE,
            'deactivated_at' => now(),
        ]);

        // 3. Historical attribution on Lead 1 remains intact
        $lead1->refresh();
        $this->assertEquals(ReferralAttributionStatus::ACTIVE, $lead1->referralAttribution?->attribution_status);
        $this->assertEquals($this->codeA->id, $lead1->referralAttribution?->referral_code_id);

        // 4. New User 2 scans the deactivated Code A
        $payload2 = $this->buildTelegramPayload(3002, $telegramId2, '/start REF_SOKHA01');
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'secret-test-token-12345')
            ->postJson(route('telegram.webhook'), $payload2)
            ->assertOk()
            ->assertJsonPath('result.status', 'inactive_referral_code');

        // Assert NO new lead was created
        $this->assertEquals(1, Lead::count());

        // Event was recorded as INACTIVE_CODE
        $this->assertDatabaseHas('referral_events', [
            'event_type' => TelegramEventType::INACTIVE_CODE->value,
            'referral_code_id' => $this->codeA->id,
        ]);
    }

    public function test_unknown_referral_code_records_event_and_creates_no_lead(): void
    {
        $payload = $this->buildTelegramPayload(4001, 888777555, '/start REF_NONEXISTENT');

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'secret-test-token-12345')
            ->postJson(route('telegram.webhook'), $payload)
            ->assertOk()
            ->assertJsonPath('result.status', 'unknown_referral_code');

        $this->assertEquals(0, Lead::count());
        $this->assertDatabaseHas('referral_events', [
            'event_type' => TelegramEventType::UNKNOWN_CODE->value,
            'event_token' => 'REF_NONEXISTENT',
        ]);
    }

    public function test_direct_start_without_code_records_start_event_and_creates_no_lead(): void
    {
        $payload = $this->buildTelegramPayload(5001, 888777666, '/start');

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'secret-test-token-12345')
            ->postJson(route('telegram.webhook'), $payload)
            ->assertOk()
            ->assertJsonPath('result.status', 'direct_welcome_sent');

        $this->assertEquals(0, Lead::count());
        $this->assertDatabaseHas('referral_events', [
            'event_type' => TelegramEventType::START->value,
        ]);
    }

    public function test_webhook_update_id_is_strictly_idempotent(): void
    {
        $payload = $this->buildTelegramPayload(6001, 888777777, '/start REF_SOKHA01');

        // First delivery
        $res1 = $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'secret-test-token-12345')
            ->postJson(route('telegram.webhook'), $payload);
        $res1->assertOk()->assertJsonPath('result.outcome', 'attributed_new_lead');

        // Second delivery (duplicate update_id)
        $res2 = $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'secret-test-token-12345')
            ->postJson(route('telegram.webhook'), $payload);
        $res2->assertOk()->assertJsonPath('result.status', 'duplicate');

        // Exactly one event, one customer, one lead
        $this->assertEquals(1, ReferralEvent::where('telegram_update_id', 6001)->count());
        $this->assertEquals(1, Lead::count());
    }

    public function test_telegram_user_profile_is_updated_on_subsequent_scans(): void
    {
        $telegramId = 888777888;

        // First scan with old username
        $payload1 = $this->buildTelegramPayload(7001, $telegramId, '/start REF_SOKHA01', 'old_handle');
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'secret-test-token-12345')
            ->postJson(route('telegram.webhook'), $payload1);

        $this->assertDatabaseHas('telegram_users', [
            'telegram_id' => $telegramId,
            'username' => 'old_handle',
        ]);

        // Second scan with new handle
        $payload2 = $this->buildTelegramPayload(7002, $telegramId, '/start REF_SOKHA01', 'new_handle');
        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'secret-test-token-12345')
            ->postJson(route('telegram.webhook'), $payload2);

        $this->assertDatabaseHas('telegram_users', [
            'telegram_id' => $telegramId,
            'username' => 'new_handle',
        ]);
    }
}
