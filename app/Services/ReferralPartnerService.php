<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PartnerStatus;
use App\Enums\PartnerType;
use App\Models\ReferralPartner;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReferralPartnerService
{
    /**
     * Create a new referral partner.
     *
     * @param  array{name: string, type: string, phone?: ?string, email?: ?string, company_name?: ?string, address?: ?string, notes?: ?string, status?: ?string}  $data
     */
    public function createPartner(array $data, User $actor): ReferralPartner
    {
        return DB::transaction(function () use ($data) {
            return ReferralPartner::create([
                'name' => trim($data['name']),
                'type' => PartnerType::from($data['type']),
                'phone' => isset($data['phone']) ? trim($data['phone']) : null,
                'email' => isset($data['email']) ? trim($data['email']) : null,
                'company_name' => isset($data['company_name']) ? trim($data['company_name']) : null,
                'address' => isset($data['address']) ? trim($data['address']) : null,
                'notes' => isset($data['notes']) ? trim($data['notes']) : null,
                'status' => isset($data['status']) ? PartnerStatus::from($data['status']) : PartnerStatus::ACTIVE,
            ]);
        });
    }

    /**
     * Update an existing referral partner.
     *
     * @param  array{name: string, type: string, phone?: ?string, email?: ?string, company_name?: ?string, address?: ?string, notes?: ?string, status: string}  $data
     */
    public function updatePartner(ReferralPartner $partner, array $data, User $actor): ReferralPartner
    {
        return DB::transaction(function () use ($partner, $data) {
            $partner->update([
                'name' => trim($data['name']),
                'type' => PartnerType::from($data['type']),
                'phone' => isset($data['phone']) ? trim($data['phone']) : null,
                'email' => isset($data['email']) ? trim($data['email']) : null,
                'company_name' => isset($data['company_name']) ? trim($data['company_name']) : null,
                'address' => isset($data['address']) ? trim($data['address']) : null,
                'notes' => isset($data['notes']) ? trim($data['notes']) : null,
                'status' => PartnerStatus::from($data['status']),
            ]);

            return $partner;
        });
    }

    /**
     * Activate a referral partner.
     */
    public function activatePartner(ReferralPartner $partner, User $actor): ReferralPartner
    {
        $partner->update(['status' => PartnerStatus::ACTIVE]);

        return $partner;
    }

    /**
     * Deactivate a referral partner (soft status deactivation).
     */
    public function deactivatePartner(ReferralPartner $partner, User $actor): ReferralPartner
    {
        $partner->update(['status' => PartnerStatus::INACTIVE]);

        return $partner;
    }

    /**
     * Delete a referral partner with dependency protection.
     *
     * @throws ValidationException
     */
    public function deletePartner(ReferralPartner $partner, User $actor): void
    {
        if ($partner->drivers()->exists()) {
            throw ValidationException::withMessages([
                'partner' => 'Cannot delete a partner with associated drivers. Deactivate the partner instead to preserve historical records.',
            ]);
        }

        DB::transaction(function () use ($partner) {
            $partner->delete();
        });
    }
}
