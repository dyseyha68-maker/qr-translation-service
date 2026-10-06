<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DriverStatus;
use App\Enums\PartnerStatus;
use App\Enums\PartnerType;
use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use App\Models\Driver;
use App\Models\ReferralPartner;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class ReferralPartnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Partner: Independent Tuk-Tuk Driver Network
        $partner1 = ReferralPartner::firstOrCreate(
            ['name' => 'Phnom Penh Tuk-Tuk Association'],
            [
                'type' => PartnerType::COMPANY,
                'phone' => '+855 12 345 678',
                'email' => 'contact@pp-tuktuk.com.kh',
                'company_name' => 'Phnom Penh Tuk-Tuk Association',
                'address' => 'Sangkat Phsar Thmei, Daun Penh, Phnom Penh',
                'notes' => 'Key partner for Riverside and Central Market passenger referrals.',
                'status' => PartnerStatus::ACTIVE,
            ]
        );

        $driver1 = Driver::firstOrCreate(
            ['phone' => '+855 92 111 222'],
            [
                'referral_partner_id' => $partner1->id,
                'name' => 'Sokha Chan',
                'email' => 'sokha.chan@pp-tuktuk.com.kh',
                'national_id' => 'KH-01020304',
                'address' => 'Tuol Kork, Phnom Penh',
                'notes' => 'Speaks basic English; frequent airport transfers.',
                'status' => DriverStatus::ACTIVE,
            ]
        );

        Vehicle::firstOrCreate(
            ['plate_number' => 'PP-1AB-1234'],
            [
                'driver_id' => $driver1->id,
                'vehicle_type' => VehicleType::TUK_TUK,
                'make' => 'Bajaj',
                'model' => 'RE Maxima',
                'color' => 'Red',
                'notes' => 'Equipped with interior company card holder.',
                'status' => VehicleStatus::ACTIVE,
            ]
        );

        // 2. Partner: VIP Taxi Fleet
        $partner2 = ReferralPartner::firstOrCreate(
            ['name' => 'Angkor Express Taxi'],
            [
                'type' => PartnerType::COMPANY,
                'phone' => '+855 23 888 999',
                'email' => 'fleet@angkortaxi.com.kh',
                'company_name' => 'Angkor Express Transport Co., Ltd.',
                'address' => 'Russian Federation Blvd, Phnom Penh',
                'notes' => 'Airport and luxury hotel taxi fleet.',
                'status' => PartnerStatus::ACTIVE,
            ]
        );

        $driver2 = Driver::firstOrCreate(
            ['phone' => '+855 88 555 444'],
            [
                'referral_partner_id' => $partner2->id,
                'name' => 'Vannak Heng',
                'email' => 'vannak@angkortaxi.com.kh',
                'national_id' => 'KH-09080706',
                'address' => 'BKK1, Phnom Penh',
                'notes' => 'Stationed at NagaWorld and luxury hotels.',
                'status' => DriverStatus::ACTIVE,
            ]
        );

        Vehicle::firstOrCreate(
            ['plate_number' => 'PP-2BC-5678'],
            [
                'driver_id' => $driver2->id,
                'vehicle_type' => VehicleType::TAXI,
                'make' => 'Toyota',
                'model' => 'Prius',
                'color' => 'White',
                'notes' => 'Air-conditioned sedan.',
                'status' => VehicleStatus::ACTIVE,
            ]
        );
    }
}
