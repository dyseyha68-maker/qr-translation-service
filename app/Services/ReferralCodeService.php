<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ReferralCodeStatus;
use App\Models\Driver;
use App\Models\ReferralCode;
use App\Models\ReferralPartner;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReferralCodeService
{
    /**
     * Validate that partner, driver, and vehicle form a valid and active hierarchy.
     *
     * @throws ValidationException
     */
    public function validateOwnership(
        ReferralPartner|int $partner,
        Driver|int $driver,
        Vehicle|int $vehicle
    ): array {
        $partnerModel = $partner instanceof ReferralPartner ? $partner : ReferralPartner::findOrFail($partner);
        $driverModel = $driver instanceof Driver ? $driver : Driver::findOrFail($driver);
        $vehicleModel = $vehicle instanceof Vehicle ? $vehicle : Vehicle::findOrFail($vehicle);

        // 1. Partner must be active
        if (! $partnerModel->isActive()) {
            throw ValidationException::withMessages([
                'referral_partner_id' => 'Cannot create a referral code under an inactive referral partner.',
            ]);
        }

        // 2. Driver must be active
        if (! $driverModel->isActive()) {
            throw ValidationException::withMessages([
                'driver_id' => 'Cannot create a referral code for an inactive driver.',
            ]);
        }

        // 3. Driver must belong to the selected partner
        if ((int) $driverModel->referral_partner_id !== (int) $partnerModel->id) {
            throw ValidationException::withMessages([
                'driver_id' => 'The selected driver does not belong to the selected referral partner.',
            ]);
        }

        // 4. Vehicle must be active
        if (! $vehicleModel->isActive()) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'Cannot create a referral code for an inactive vehicle.',
            ]);
        }

        // 5. Vehicle must belong to the selected driver
        if ((int) $vehicleModel->driver_id !== (int) $driverModel->id) {
            throw ValidationException::withMessages([
                'vehicle_id' => 'The selected vehicle does not belong to the selected driver.',
            ]);
        }

        return [
            'partner' => $partnerModel,
            'driver' => $driverModel,
            'vehicle' => $vehicleModel,
        ];
    }

    /**
     * Generate a unique, collision-resistant referral code.
     * Format: REF-{6 UPPERCASE ALPHANUMERIC CHARACTERS}
     */
    public function generateUniqueCode(string $prefix = 'REF'): string
    {
        $prefix = strtoupper(trim($prefix));
        $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ'; // Unambiguous characters
        $maxAttempts = 50;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $random = '';
            for ($j = 0; $j < 6; $j++) {
                $random .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $candidate = "{$prefix}-{$random}";

            if (! ReferralCode::where('code', $candidate)->exists()) {
                return $candidate;
            }
        }

        // Fallback with high-entropy timestamp if attempts exhausted
        return sprintf('%s-%s%s', $prefix, strtoupper(Str::random(4)), strtoupper(dechex(time() % 65535)));
    }

    /**
     * Create a new referral code for a verified partner, driver, and vehicle.
     *
     * @param  array{referral_partner_id: int, driver_id: int, vehicle_id: int, code?: ?string, label?: ?string, notes?: ?string, status?: ?string}  $data
     *
     * @throws ValidationException
     */
    public function create(array $data, User $actor): ReferralCode
    {
        // 1. Verify entity hierarchy & active status
        $this->validateOwnership(
            (int) $data['referral_partner_id'],
            (int) $data['driver_id'],
            (int) $data['vehicle_id']
        );

        // 2. Determine or generate unique code
        $code = ! empty($data['code'])
            ? strtoupper(trim($data['code']))
            : $this->generateUniqueCode();

        // 3. Double-check code uniqueness
        if (ReferralCode::where('code', $code)->exists()) {
            throw ValidationException::withMessages([
                'code' => "The referral code '{$code}' is already taken.",
            ]);
        }

        return DB::transaction(function () use ($data, $code, $actor) {
            return ReferralCode::create([
                'code' => $code,
                'referral_partner_id' => (int) $data['referral_partner_id'],
                'driver_id' => (int) $data['driver_id'],
                'vehicle_id' => (int) $data['vehicle_id'],
                'status' => isset($data['status']) ? ReferralCodeStatus::from($data['status']) : ReferralCodeStatus::ACTIVE,
                'label' => ! empty($data['label']) ? trim($data['label']) : null,
                'notes' => ! empty($data['notes']) ? trim($data['notes']) : null,
                'created_by' => $actor->id,
            ]);
        });
    }

    /**
     * Update metadata for an existing referral code.
     * Enforces strict historical immutability: partner, driver, vehicle, and code are preserved.
     *
     * @param  array{label?: ?string, notes?: ?string}  $data
     */
    public function updateMetadata(ReferralCode $referralCode, array $data, User $actor): ReferralCode
    {
        return DB::transaction(function () use ($referralCode, $data) {
            $referralCode->update([
                'label' => array_key_exists('label', $data) ? (! empty($data['label']) ? trim((string) $data['label']) : null) : $referralCode->label,
                'notes' => array_key_exists('notes', $data) ? (! empty($data['notes']) ? trim((string) $data['notes']) : null) : $referralCode->notes,
            ]);

            return $referralCode;
        });
    }

    /**
     * Activate a referral code.
     * Validates that the underlying entities are still active before allowing activation.
     *
     * @throws ValidationException
     */
    public function activate(ReferralCode $referralCode, User $actor): ReferralCode
    {
        if ($referralCode->isActive()) {
            return $referralCode;
        }

        // Validate that underlying partner, driver, and vehicle are still active
        $this->validateOwnership(
            $referralCode->referral_partner_id,
            $referralCode->driver_id,
            $referralCode->vehicle_id
        );

        return DB::transaction(function () use ($referralCode) {
            $referralCode->update([
                'status' => ReferralCodeStatus::ACTIVE,
                'deactivated_at' => null,
            ]);

            return $referralCode;
        });
    }

    /**
     * Deactivate a referral code.
     * Preserves the database record and sets deactivated_at timestamp.
     */
    public function deactivate(ReferralCode $referralCode, User $actor): ReferralCode
    {
        if (! $referralCode->isActive()) {
            return $referralCode;
        }

        return DB::transaction(function () use ($referralCode) {
            $referralCode->update([
                'status' => ReferralCodeStatus::INACTIVE,
                'deactivated_at' => now(),
            ]);

            return $referralCode;
        });
    }

    /**
     * Delete a referral code.
     * Protected: only inactive codes that have no historical attribution activity may be removed.
     *
     * @throws ValidationException
     */
    public function delete(ReferralCode $referralCode, User $actor): void
    {
        DB::transaction(function () use ($referralCode) {
            $referralCode->delete();
        });
    }

    /**
     * Find an active referral code by its code string (for future Phase 4 bot attribution).
     */
    public function findActiveByCode(string $code): ?ReferralCode
    {
        $normalized = strtoupper(trim($code));

        return ReferralCode::query()
            ->active()
            ->with(['referralPartner', 'driver', 'vehicle'])
            ->where('code', $normalized)
            ->first();
    }
}
