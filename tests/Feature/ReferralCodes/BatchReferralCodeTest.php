<?php

declare(strict_types=1);

namespace Tests\Feature\ReferralCodes;

use App\Enums\ReferralCodeStatus;
use App\Enums\RoleName;
use App\Models\ReferralCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BatchReferralCodeTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
        $this->seed(\Database\Seeders\ReferralPartnerSeeder::class);

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole(RoleName::SUPER_ADMIN->value);
    }

    public function test_authorized_user_can_generate_batch_of_unassigned_codes(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('referral-codes.batch-generate'), [
                'quantity' => 5,
                'prefix' => 'REF-TK',
                'label' => 'Office Pre-print Batch 1',
            ]);

        $response->assertRedirect(route('referral-codes.index', ['status' => ReferralCodeStatus::UNASSIGNED->value]));
        $this->assertEquals(5, ReferralCode::unassigned()->count());

        $first = ReferralCode::unassigned()->first();
        $this->assertNotNull($first);
        $this->assertStringStartsWith('REF-TK-', $first->code);
        $this->assertNull($first->driver_id);
        $this->assertNull($first->vehicle_id);
    }

    public function test_authorized_user_can_quick_assign_driver_to_unassigned_code(): void
    {
        /** @var ReferralCode $code */
        $code = ReferralCode::create([
            'code' => 'REF-TK-TEST99',
            'referral_partner_id' => null,
            'driver_id' => null,
            'vehicle_id' => null,
            'status' => ReferralCodeStatus::UNASSIGNED,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('referral-codes.assign-driver', $code->id), [
                'driver_name' => 'Bong Sokha TukTuk',
                'driver_phone' => '+855 12 333 444',
                'vehicle_plate' => 'PP-1AB-9999',
                'vehicle_type' => 'TUK_TUK',
                'notes' => 'ABA: 001 222 333',
            ]);

        $response->assertRedirect();
        $code->refresh();

        $this->assertEquals(ReferralCodeStatus::ACTIVE, $code->status);
        $this->assertNotNull($code->driver);
        $this->assertEquals('Bong Sokha TukTuk', $code->driver->name);
        $this->assertEquals('PP-1AB-9999', $code->vehicle->plate_number);
    }

    public function test_batch_print_screen_is_accessible(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('referral-codes.batch-print'));

        $response->assertOk();
    }
}

