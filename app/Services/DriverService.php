<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DriverStatus;
use App\Models\Driver;
use App\Models\ReferralPartner;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DriverService
{
    /**
     * Create a new driver.
     *
     * @param  array{referral_partner_id: int, name: string, phone: string, email?: ?string, national_id?: ?string, address?: ?string, notes?: ?string, status?: ?string}  $data
     *
     * @throws ValidationException
     */
    public function createDriver(array $data, User $actor): Driver
    {
        $partner = ReferralPartner::findOrFail($data['referral_partner_id']);

        if (! $partner->isActive()) {
            throw ValidationException::withMessages([
                'referral_partner_id' => 'Cannot assign a driver to an inactive referral partner.',
            ]);
        }

        return DB::transaction(function () use ($data) {
            return Driver::create([
                'referral_partner_id' => (int) $data['referral_partner_id'],
                'name' => trim($data['name']),
                'phone' => !empty($data['phone']) ? trim((string) $data['phone']) : null,
                'email' => isset($data['email']) ? trim($data['email']) : null,
                'national_id' => isset($data['national_id']) ? trim($data['national_id']) : null,
                'address' => isset($data['address']) ? trim($data['address']) : null,
                'notes' => isset($data['notes']) ? trim($data['notes']) : null,
                'status' => isset($data['status']) ? DriverStatus::from($data['status']) : DriverStatus::ACTIVE,
            ]);
        });
    }

    /**
     * Update an existing driver.
     *
     * @param  array{referral_partner_id: int, name: string, phone: string, email?: ?string, national_id?: ?string, address?: ?string, notes?: ?string, status: string}  $data
     *
     * @throws ValidationException
     */
    public function updateDriver(Driver $driver, array $data, User $actor): Driver
    {
        $partner = ReferralPartner::findOrFail($data['referral_partner_id']);

        if (! $partner->isActive() && (int) $driver->referral_partner_id !== (int) $data['referral_partner_id']) {
            throw ValidationException::withMessages([
                'referral_partner_id' => 'Cannot reassign a driver to an inactive referral partner.',
            ]);
        }

        return DB::transaction(function () use ($driver, $data) {
            $driver->update([
                'referral_partner_id' => (int) $data['referral_partner_id'],
                'name' => trim($data['name']),
                'phone' => array_key_exists('phone', $data)
                    ? (!empty($data['phone']) ? trim((string) $data['phone']) : null)
                    : $driver->phone,
                'email' => array_key_exists('email', $data) ? (!empty($data['email']) ? trim((string) $data['email']) : null) : $driver->email,
                'national_id' => array_key_exists('national_id', $data) ? (!empty($data['national_id']) ? trim((string) $data['national_id']) : null) : $driver->national_id,
                'address' => array_key_exists('address', $data) ? (!empty($data['address']) ? trim((string) $data['address']) : null) : $driver->address,
                'notes' => array_key_exists('notes', $data) ? (!empty($data['notes']) ? trim((string) $data['notes']) : null) : $driver->notes,
                'status' => DriverStatus::from($data['status']),
            ]);

            return $driver;
        });
    }

    /**
     * Activate a driver.
     */
    public function activateDriver(Driver $driver, User $actor): Driver
    {
        $driver->update(['status' => DriverStatus::ACTIVE]);

        return $driver;
    }

    /**
     * Deactivate a driver (soft status deactivation).
     */
    public function deactivateDriver(Driver $driver, User $actor): Driver
    {
        $driver->update(['status' => DriverStatus::INACTIVE]);

        return $driver;
    }

    /**
     * Delete a driver with dependency protection.
     *
     * @throws ValidationException
     */
    public function deleteDriver(Driver $driver, User $actor): void
    {
        if ($driver->vehicles()->exists()) {
            throw ValidationException::withMessages([
                'driver' => 'Cannot delete a driver with associated vehicles. Deactivate the driver instead to preserve historical records.',
            ]);
        }

        DB::transaction(function () use ($driver) {
            $driver->delete();
        });
    }
}
