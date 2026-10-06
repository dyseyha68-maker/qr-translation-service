<?php

declare(strict_types=1);

namespace Tests\Feature\ReferralPartners;

use App\Enums\PartnerStatus;
use App\Enums\PartnerType;
use App\Enums\RoleName;
use App\Models\Driver;
use App\Models\ReferralPartner;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralPartnerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;
    private User $finance;

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
    }

    public function test_authorized_user_can_view_partners_list(): void
    {
        ReferralPartner::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get(route('referral-partners.index'));

        $response->assertOk();
    }

    public function test_staff_with_view_permission_can_view_partners(): void
    {
        ReferralPartner::factory()->count(2)->create();

        $response = $this->actingAs($this->staff)->get(route('referral-partners.index'));

        $response->assertOk();
    }

    public function test_finance_without_permission_cannot_view_partners(): void
    {
        $response = $this->actingAs($this->finance)->get(route('referral-partners.index'));

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get(route('referral-partners.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authorized_user_can_create_partner(): void
    {
        $payload = [
            'name' => 'Phnom Penh Tuk-Tuk Association',
            'type' => PartnerType::COMPANY->value,
            'company_name' => 'PPTA Co., Ltd.',
            'phone' => '+855 12 888 999',
            'email' => 'contact@ppta.com.kh',
            'address' => 'Sangkat Phsar Thmei, Phnom Penh',
            'notes' => 'Primary airport partner',
            'status' => PartnerStatus::ACTIVE->value,
        ];

        $response = $this->actingAs($this->admin)->post(route('referral-partners.store'), $payload);

        $response->assertRedirect(route('referral-partners.index'));
        $this->assertDatabaseHas('referral_partners', [
            'name' => 'Phnom Penh Tuk-Tuk Association',
            'company_name' => 'PPTA Co., Ltd.',
            'status' => PartnerStatus::ACTIVE->value,
        ]);
    }

    public function test_staff_cannot_create_partner(): void
    {
        $payload = [
            'name' => 'Unauthorized Partner',
            'type' => PartnerType::DRIVER->value,
            'status' => PartnerStatus::ACTIVE->value,
        ];

        $response = $this->actingAs($this->staff)->post(route('referral-partners.store'), $payload);

        $response->assertForbidden();
        $this->assertDatabaseMissing('referral_partners', [
            'name' => 'Unauthorized Partner',
        ]);
    }

    public function test_validation_requires_name_and_type_and_status(): void
    {
        $response = $this->actingAs($this->admin)->post(route('referral-partners.store'), []);

        $response->assertSessionHasErrors(['name', 'type', 'status']);
    }

    public function test_validation_rejects_invalid_partner_type_or_status(): void
    {
        $response = $this->actingAs($this->admin)->post(route('referral-partners.store'), [
            'name' => 'Invalid Enums Partner',
            'type' => 'NON_EXISTENT_TYPE',
            'status' => 'UNKNOWN_STATUS',
        ]);

        $response->assertSessionHasErrors(['type', 'status']);
    }

    public function test_authorized_user_can_update_partner(): void
    {
        $partner = ReferralPartner::factory()->create([
            'name' => 'Original Partner Name',
        ]);

        $response = $this->actingAs($this->admin)->put(route('referral-partners.update', $partner), [
            'name' => 'Updated Partner Name',
            'type' => $partner->type->value,
            'status' => $partner->status->value,
        ]);

        $response->assertRedirect(route('referral-partners.index'));
        $this->assertDatabaseHas('referral_partners', [
            'id' => $partner->id,
            'name' => 'Updated Partner Name',
        ]);
    }

    public function test_authorized_user_can_deactivate_and_activate_partner(): void
    {
        $partner = ReferralPartner::factory()->create([
            'status' => PartnerStatus::ACTIVE,
        ]);

        // Deactivate
        $response = $this->actingAs($this->admin)->patch(route('referral-partners.deactivate', $partner));
        $response->assertRedirect();
        $this->assertDatabaseHas('referral_partners', [
            'id' => $partner->id,
            'status' => PartnerStatus::INACTIVE->value,
        ]);

        // Activate
        $response = $this->actingAs($this->admin)->patch(route('referral-partners.activate', $partner));
        $response->assertRedirect();
        $this->assertDatabaseHas('referral_partners', [
            'id' => $partner->id,
            'status' => PartnerStatus::ACTIVE->value,
        ]);
    }

    public function test_partner_with_drivers_cannot_be_deleted(): void
    {
        $partner = ReferralPartner::factory()->create();
        Driver::factory()->create(['referral_partner_id' => $partner->id]);

        $response = $this->actingAs($this->admin)->delete(route('referral-partners.destroy', $partner));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('referral_partners', ['id' => $partner->id]);
    }

    public function test_partner_without_drivers_can_be_deleted(): void
    {
        $partner = ReferralPartner::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('referral-partners.destroy', $partner));

        $response->assertRedirect(route('referral-partners.index'));
        $this->assertDatabaseMissing('referral_partners', ['id' => $partner->id]);
    }
}
