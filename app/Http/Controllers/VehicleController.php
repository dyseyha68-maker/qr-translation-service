<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use App\Http\Requests\Vehicle\StoreVehicleRequest;
use App\Http\Requests\Vehicle\UpdateVehicleRequest;
use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\VehicleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    public function __construct(
        protected VehicleService $vehicleService
    ) {}

    /**
     * Display a listing of vehicles.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Vehicle::class);

        /** @var User $currentUser */
        $currentUser = $request->user();

        $search = $request->query('search');
        $vehicleType = $request->query('vehicle_type');
        $status = $request->query('status');
        $driverId = $request->query('driver_id');

        $vehicles = Vehicle::query()
            ->with(['driver.referralPartner'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('plate_number', 'like', "%{$search}%")
                        ->orWhere('make', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhereHas('driver', fn ($dq) => $dq->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($vehicleType, fn ($query, $vehicleType) => $query->where('vehicle_type', $vehicleType))
            ->when($status, fn ($query, $status) => $query->where('status', $status))
            ->when($driverId, fn ($query, $driverId) => $query->where('driver_id', $driverId))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Vehicle $vehicle) => [
                'id' => $vehicle->id,
                'plate_number' => $vehicle->plate_number,
                'vehicle_type' => $vehicle->vehicle_type->value,
                'make' => $vehicle->make,
                'model' => $vehicle->model,
                'color' => $vehicle->color,
                'status' => $vehicle->status->value,
                'driver' => [
                    'id' => $vehicle->driver->id,
                    'name' => $vehicle->driver->name,
                ],
                'partner' => [
                    'id' => $vehicle->driver->referralPartner->id,
                    'name' => $vehicle->driver->referralPartner->name,
                ],
                'created_at' => $vehicle->created_at?->format('Y-m-d') ?? '',
                'can' => [
                    'view' => $currentUser->can('view', $vehicle),
                    'edit' => $currentUser->can('update', $vehicle),
                    'delete' => $currentUser->can('delete', $vehicle),
                ],
            ]);

        $driversList = Driver::query()
            ->orderBy('name', 'asc')
            ->get(['id', 'name']);

        return Inertia::render('Vehicles/Index', [
            'vehicles' => $vehicles,
            'drivers' => $driversList,
            'filters' => [
                'search' => $search,
                'vehicle_type' => $vehicleType,
                'status' => $status,
                'driver_id' => $driverId ? (int) $driverId : null,
            ],
            'vehicleTypes' => VehicleType::values(),
            'statuses' => VehicleStatus::values(),
            'can' => [
                'create_vehicle' => $currentUser->can('create', Vehicle::class),
            ],
        ]);
    }

    /**
     * Show the form for creating a new vehicle.
     */
    public function create(): Response
    {
        Gate::authorize('create', Vehicle::class);

        $activeDrivers = Driver::active()
            ->with('referralPartner')
            ->orderBy('name', 'asc')
            ->get()
            ->map(fn (Driver $d) => [
                'id' => $d->id,
                'name' => "{$d->name} ({$d->referralPartner->name})",
            ]);

        return Inertia::render('Vehicles/Create', [
            'drivers' => $activeDrivers,
            'vehicleTypes' => VehicleType::values(),
            'statuses' => VehicleStatus::values(),
        ]);
    }

    /**
     * Store a newly created vehicle.
     */
    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();

        $this->vehicleService->createVehicle($request->validated(), $currentUser);

        return redirect()->route('vehicles.index')->with('success', 'Vehicle registered successfully.');
    }

    /**
     * Display the specified vehicle.
     */
    public function show(Vehicle $vehicle, Request $request): Response
    {
        Gate::authorize('view', $vehicle);

        /** @var User $currentUser */
        $currentUser = $request->user();

        $vehicle->load(['driver.referralPartner']);

        return Inertia::render('Vehicles/Show', [
            'vehicle' => [
                'id' => $vehicle->id,
                'plate_number' => $vehicle->plate_number,
                'vehicle_type' => $vehicle->vehicle_type->value,
                'make' => $vehicle->make,
                'model' => $vehicle->model,
                'color' => $vehicle->color,
                'notes' => $vehicle->notes,
                'status' => $vehicle->status->value,
                'driver' => [
                    'id' => $vehicle->driver->id,
                    'name' => $vehicle->driver->name,
                    'phone' => $vehicle->driver->phone,
                    'status' => $vehicle->driver->status->value,
                ],
                'partner' => [
                    'id' => $vehicle->driver->referralPartner->id,
                    'name' => $vehicle->driver->referralPartner->name,
                    'type' => $vehicle->driver->referralPartner->type->value,
                ],
                'created_at' => $vehicle->created_at?->format('Y-m-d H:i') ?? '',
                'updated_at' => $vehicle->updated_at?->format('Y-m-d H:i') ?? '',
            ],
            'can' => [
                'edit' => $currentUser->can('update', $vehicle),
                'delete' => $currentUser->can('delete', $vehicle),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified vehicle.
     */
    public function edit(Vehicle $vehicle): Response
    {
        Gate::authorize('update', $vehicle);

        $drivers = Driver::with('referralPartner')
            ->orderBy('name', 'asc')
            ->get()
            ->map(fn (Driver $d) => [
                'id' => $d->id,
                'name' => "{$d->name} ({$d->referralPartner->name})" . ($d->isActive() ? '' : ' [INACTIVE]'),
            ]);

        return Inertia::render('Vehicles/Edit', [
            'vehicle' => [
                'id' => $vehicle->id,
                'driver_id' => $vehicle->driver_id,
                'vehicle_type' => $vehicle->vehicle_type->value,
                'plate_number' => $vehicle->plate_number,
                'make' => $vehicle->make,
                'model' => $vehicle->model,
                'color' => $vehicle->color,
                'notes' => $vehicle->notes,
                'status' => $vehicle->status->value,
            ],
            'drivers' => $drivers,
            'vehicleTypes' => VehicleType::values(),
            'statuses' => VehicleStatus::values(),
        ]);
    }

    /**
     * Update the specified vehicle.
     */
    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();

        $this->vehicleService->updateVehicle($vehicle, $request->validated(), $currentUser);

        return redirect()->route('vehicles.index')->with('success', 'Vehicle updated successfully.');
    }

    /**
     * Remove the specified vehicle from storage.
     */
    public function destroy(Vehicle $vehicle, Request $request): RedirectResponse
    {
        Gate::authorize('delete', $vehicle);

        /** @var User $currentUser */
        $currentUser = $request->user();

        try {
            $this->vehicleService->deleteVehicle($vehicle, $currentUser);
            return redirect()->route('vehicles.index')->with('success', 'Vehicle deleted successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? 'Cannot delete vehicle.';
            return redirect()->back()->with('error', $message)->withErrors($e->errors());
        }
    }

    /**
     * Activate the specified vehicle.
     */
    public function activate(Vehicle $vehicle, Request $request): RedirectResponse
    {
        Gate::authorize('update', $vehicle);

        /** @var User $currentUser */
        $currentUser = $request->user();

        $this->vehicleService->activateVehicle($vehicle, $currentUser);

        return redirect()->back()->with('success', 'Vehicle activated successfully.');
    }

    /**
     * Deactivate the specified vehicle.
     */
    public function deactivate(Vehicle $vehicle, Request $request): RedirectResponse
    {
        Gate::authorize('update', $vehicle);

        /** @var User $currentUser */
        $currentUser = $request->user();

        $this->vehicleService->deactivateVehicle($vehicle, $currentUser);

        return redirect()->back()->with('success', 'Vehicle deactivated successfully.');
    }
}
