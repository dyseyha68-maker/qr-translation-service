<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ReferralCodeStatus;
use App\Models\Driver;
use App\Models\ReferralCode;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReferralCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $creator = User::first() ?? User::factory()->create();

        // Retrieve active drivers that have vehicles
        $drivers = Driver::with(['referralPartner', 'vehicles'])
            ->where('status', 'ACTIVE')
            ->has('vehicles')
            ->get();

        $counter = 1;
        foreach ($drivers as $driver) {
            foreach ($driver->vehicles as $vehicle) {
                if ($vehicle->isActive()) {
                    $prefix = $vehicle->vehicle_type->value === 'TUK_TUK' ? 'TK' : 'TX';
                    $code = sprintf('REF-%s%03d', $prefix, $counter++);

                    ReferralCode::firstOrCreate(
                        ['code' => $code],
                        [
                            'referral_partner_id' => $driver->referral_partner_id,
                            'driver_id' => $driver->id,
                            'vehicle_id' => $vehicle->id,
                            'status' => ReferralCodeStatus::ACTIVE,
                            'label' => "Primary vehicle QR sticker ({$vehicle->plate_number})",
                            'notes' => 'Generated during Phase 3 initial seeding.',
                            'created_by' => $creator->id,
                        ]
                    );
                }
            }
        }
    }
}
