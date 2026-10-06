<?php

declare(strict_types=1);

namespace Tests\Feature\Leads;

use App\Enums\DriverStatus;
use App\Enums\LeadStatus;
use App\Enums\PartnerStatus;
use App\Enums\ReferralAttributionStatus;
use App\Enums\ReferralCodeStatus;
use App\Enums\RoleName;
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
use Tests\TestCase;

class LeadManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;
    private User $finance;

    private ReferralCode $codeA;
    private ReferralCode $codeB;
    private Lead $lead;

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
        $driverA = Driver::factory()->create(['referral_partner_id' => $partner->id, 'status' => DriverStatus::ACTIVE]);
        $vehicleA = Vehicle::factory()->create(['driver_id' => $driverA->id, 'status' => VehicleStatus::ACTIVE]);
        $this->codeA = ReferralCode::factory()->create([
            'referral_partner_id' => $partner->id,
            'driver_id' => $driverA->id,
            'vehicle_id' => $vehicleA->id,
            'status' => ReferralCodeStatus::ACTIVE,
        ]);

        $driverB = Driver::factory()->create(['referral_partner_id' => $partner->id, 'status' => DriverStatus::ACTIVE]);
        $vehicleB = Vehicle::factory()->create(['driver_id' => $driverB->id, 'status' => VehicleStatus::ACTIVE]);
        $this->codeB = ReferralCode::factory()->create([
            'referral_partner_id' => $partner->id,
            'driver_id' => $driverB->id,
            'vehicle_id' => $vehicleB->id,
            'status' => ReferralCodeStatus::ACTIVE,
        ]);

        $telegramUser = TelegramUser::factory()->create();
        $customer = Customer::factory()->create(['telegram_user_id' => $telegramUser->id]);
        $event = ReferralEvent::factory()->create([
            'referral_code_id' => $this->codeA->id,
            'telegram_user_id' => $telegramUser->id,
        ]);

        $this->lead = Lead::factory()->create([
            'customer_id' => $customer->id,
            'telegram_user_id' => $telegramUser->id,
            'status' => LeadStatus::NEW,
        ]);

        $attribution = ReferralAttribution::create([
            'referral_event_id' => $event->id,
            'referral_code_id' => $this->codeA->id,
            'telegram_user_id' => $telegramUser->id,
            'customer_id' => $customer->id,
            'lead_id' => $this->lead->id,
            'referral_partner_id' => $partner->id,
            'driver_id' => $driverA->id,
            'vehicle_id' => $vehicleA->id,
            'attribution_status' => ReferralAttributionStatus::ACTIVE,
            'attributed_at' => now(),
        ]);

        $this->lead->update(['referral_attribution_id' => $attribution->id]);
    }

    public function test_staff_and_admin_can_view_leads_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('leads.index'))
            ->assertOk();

        $this->actingAs($this->staff)
            ->get(route('leads.index'))
            ->assertOk();
    }

    public function test_finance_cannot_view_leads_index(): void
    {
        $this->actingAs($this->finance)
            ->get(route('leads.index'))
            ->assertForbidden();
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $this->get(route('leads.index'))
            ->assertRedirect(route('login'));
    }

    public function test_staff_can_view_lead_detail(): void
    {
        $this->actingAs($this->staff)
            ->get(route('leads.show', $this->lead->id))
            ->assertOk();
    }

    public function test_staff_can_update_lead_status(): void
    {
        $this->actingAs($this->staff)
            ->patch(route('leads.update', $this->lead->id), [
                'status' => LeadStatus::CONTACTED->value,
                'title' => 'Updated Inquiry Title',
                'service_type' => 'Notarized Power of Attorney',
                'description' => 'Customer confirmed appointment tomorrow.',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('leads', [
            'id' => $this->lead->id,
            'status' => LeadStatus::CONTACTED->value,
            'service_type' => 'Notarized Power of Attorney',
        ]);
    }

    public function test_staff_can_assign_lead_to_team_member(): void
    {
        $this->actingAs($this->staff)
            ->post(route('leads.assign', $this->lead->id), [
                'assigned_to' => $this->staff->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('leads', [
            'id' => $this->lead->id,
            'assigned_to' => $this->staff->id,
        ]);

        $this->assertDatabaseHas('lead_notes', [
            'lead_id' => $this->lead->id,
            'user_id' => $this->staff->id,
        ]);
    }

    public function test_staff_can_add_internal_note(): void
    {
        $this->actingAs($this->staff)
            ->post(route('leads.notes.store', $this->lead->id), [
                'note' => 'Customer called to ask about Khmer to English translation fees.',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('lead_notes', [
            'lead_id' => $this->lead->id,
            'user_id' => $this->staff->id,
            'note' => 'Customer called to ask about Khmer to English translation fees.',
        ]);
    }

    public function test_admin_can_manually_change_attribution_with_reason(): void
    {
        $oldAttributionId = $this->lead->referral_attribution_id;

        $this->actingAs($this->admin)
            ->post(route('leads.change-attribution', $this->lead->id), [
                'referral_code_id' => $this->codeB->id,
                'reason' => 'Passenger clarified they took driver Visal taxi ride.',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        // Previous attribution status must be CHANGED
        $this->assertDatabaseHas('referral_attributions', [
            'id' => $oldAttributionId,
            'attribution_status' => ReferralAttributionStatus::CHANGED->value,
            'change_reason' => 'Passenger clarified they took driver Visal taxi ride.',
        ]);

        // New attribution must be ACTIVE pointing to code B
        $this->lead->refresh();
        $this->assertNotEquals($oldAttributionId, $this->lead->referral_attribution_id);
        $this->assertEquals($this->codeB->id, $this->lead->referralAttribution?->referral_code_id);
        $this->assertEquals(ReferralAttributionStatus::ACTIVE, $this->lead->referralAttribution?->attribution_status);

        // Audit note recorded
        $this->assertDatabaseHas('lead_notes', [
            'lead_id' => $this->lead->id,
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_changing_attribution_fails_without_reason(): void
    {
        $this->actingAs($this->admin)
            ->post(route('leads.change-attribution', $this->lead->id), [
                'referral_code_id' => $this->codeB->id,
                'reason' => '',
            ])
            ->assertSessionHasErrors(['reason']);
    }

    public function test_staff_cannot_change_attribution(): void
    {
        $this->actingAs($this->staff)
            ->post(route('leads.change-attribution', $this->lead->id), [
                'referral_code_id' => $this->codeB->id,
                'reason' => 'Unauthorized attempt',
            ])
            ->assertForbidden();
    }

    public function test_finance_cannot_change_attribution(): void
    {
        $this->actingAs($this->finance)
            ->post(route('leads.change-attribution', $this->lead->id), [
                'referral_code_id' => $this->codeB->id,
                'reason' => 'Unauthorized finance attempt',
            ])
            ->assertForbidden();
    }
}
