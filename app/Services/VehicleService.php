<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VehicleService
{
    /**
     * Normalize plate numbers.
     */
    public function normalizePlateNumber(string $plate): string
    {
        return strtoupper(trim((string) preg_replace('/\s+/', ' ', $plate)));
    }

    /**
     * Create a new vehicle.
     *
     * @param  array{driver_id: int, vehicle_type: string, plate_number: string, make?: ?string, model?: ?string, color?: ?string, notes?: ?string, status?: ?string}  $data
     *
     * @throws ValidationException
     */
    public function createVehicle(array $data, User $actor): Vehicle
    {
        $driver = Driver::findOrFail($data['driver_id']);

        if (! $driver->isActive()) {
            throw ValidationException::withMessages([
                'driver_id' => 'Cannot assign a vehicle to an inactive driver.',
            ]);
        }

        $normalizedPlate = $this->normalizePlateNumber($data['plate_number']);
        $status = isset($data['status']) ? VehicleStatus::from($data['status']) : VehicleStatus::ACTIVE;

        // Prevent accidental duplicate ACTIVE vehicles with the same plate number
        if ($status === VehicleStatus::ACTIVE) {
            $existingActive = Vehicle::where('plate_number', $normalizedPlate)
                ->where('status', VehicleStatus::ACTIVE->value)
                ->exists();

            if ($existingActive) {
                throw ValidationException::withMessages([
                    'plate_number' => "An active vehicle with plate number '{$normalizedPlate}' already exists.",
                ]);
            }
        }

        return DB::transaction(function () use ($data, $normalizedPlate, $status) {
            return Vehicle::create([
                'driver_id' => (int) $data['driver_id'],
                'vehicle_type' => VehicleType::from($data['vehicle_type']),
                'plate_number' => $normalizedPlate,
                'make' => isset($data['make']) ? trim($data['make']) : null,
                'model' => isset($data['model']) ? trim($data['model']) : null,
                'color' => isset($data['color']) ? trim($data['color']) : null,
                'notes' => isset($data['notes']) ? trim($data['notes']) : null,
                'status' => $status,
            ]);
        });
    }

    /**
     * Update an existing vehicle.
     *
     * @param  array{driver_id: int, vehicle_type: string, plate_number: string, make?: ?string, model?: ?string, color?: ?string, notes?: ?string, status: string}  $data
     *
     * @throws ValidationException
     */
    public function updateVehicle(Vehicle $vehicle, array $data, User $actor): Vehicle
    {
        $driver = Driver::findOrFail($data['driver_id']);

        if (! $driver->isActive() && (int) $vehicle->driver_id !== (int) $data['driver_id']) {
            throw ValidationException::withMessages([
                'driver_id' => 'Cannot reassign a vehicle to an inactive driver.',
            ]);
        }

        $normalizedPlate = $this->normalizePlateNumber($data['plate_number']);
        $newStatus = VehicleStatus::from($data['status']);

        // Check duplicate active plate excluding current vehicle
        if ($newStatus === VehicleStatus::ACTIVE) {
            $existingActive = Vehicle::where('plate_number', $normalizedPlate)
                ->where('status', VehicleStatus::ACTIVE->value)
                ->where('id', '!=', $vehicle->id)
                ->exists();

            if ($existingActive) {
                throw ValidationException::withMessages([
                    'plate_number' => "An active vehicle with plate number '{$normalizedPlate}' already exists.",
                ]);
            }
        }

        return DB::transaction(function () use ($vehicle, $data, $normalizedPlate, $newStatus) {
            $vehicle->update([
                'driver_id' => (int) $data['driver_id'],
                'vehicle_type' => VehicleType::from($data['vehicle_type']),
                'plate_number' => $normalizedPlate,
                'make' => isset($data['make']) ? trim($data['make']) : null,
                'model' => isset($data['model']) ? trim($data['model']) : null,
                'color' => isset($data['color']) ? trim($data['color']) : null,
                'notes' => isset($data['notes']) ? trim($data['notes']) : null,
                'status' => $newStatus,
            ]);

            return $vehicle;
        });
    }

    /**
     * Activate a vehicle.
     *
     * @throws ValidationException
     */
    public function activateVehicle(Vehicle $vehicle, User $actor): Vehicle
    {
        // Ensure no other active vehicle has the same plate number
        $existingActive = Vehicle::where('plate_number', $vehicle->plate_number)
            ->where('status', VehicleStatus::ACTIVE->value)
            ->where('id', '!=', $vehicle->id)
            ->exists();

        if ($existingActive) {
            throw ValidationException::withMessages([
                'plate_number' => "Cannot activate vehicle: another active vehicle with plate number '{$vehicle->plate_number}' already exists.",
            ]);
        }

        $vehicle->update(['status' => VehicleStatus::ACTIVE]);

        return $vehicle;
    }

    /**
     * Deactivate a vehicle (soft status deactivation).
     */
    public function deactivateVehicle(Vehicle $vehicle, User $actor): Vehicle
    {
        $vehicle->update(['status' => VehicleStatus::INACTIVE]);

        return $vehicle;
    }

    /**
     * Delete a vehicle.
     */
    public function deleteVehicle(Vehicle $vehicle, User $actor): void
    {
        DB::transaction(function () use ($vehicle) {
            $vehicle->delete();
        });
    }
}
