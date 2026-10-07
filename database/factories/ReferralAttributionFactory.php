<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReferralAttributionStatus;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Lead;
use App\Models\ReferralAttribution;
use App\Models\ReferralCode;
use App\Models\ReferralEvent;
use App\Models\ReferralPartner;
use App\Models\TelegramUser;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReferralAttribution>
 */
class ReferralAttributionFactory extends Factory
{
    protected $model = ReferralAttribution::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $partner = ReferralPartner::factory();
        $driver = Driver::factory()->for($partner);
        $vehicle = Vehicle::factory()->for($driver);
        $code = ReferralCode::factory()->for($partner)->for($driver)->for($vehicle);
        $tgUser = TelegramUser::factory();
        $event = ReferralEvent::factory()->for($code)->for($tgUser);
        $customer = Customer::factory()->for($tgUser);

        return [
            'referral_event_id' => $event,
            'referral_code_id' => $code,
            'telegram_user_id' => $tgUser,
            'customer_id' => $customer,
            'lead_id' => null,
            'referral_partner_id' => $partner,
            'driver_id' => $driver,
            'vehicle_id' => $vehicle,
            'attribution_status' => ReferralAttributionStatus::ACTIVE,
            'attributed_at' => Carbon::now(),
        ];
    }
}
