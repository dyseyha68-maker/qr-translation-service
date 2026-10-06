<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\DriverStatus;
use App\Http\Requests\Driver\StoreDriverRequest;
use App\Http\Requests\Driver\UpdateDriverRequest;
use App\Models\Driver;
use App\Models\ReferralPartner;
use App\Models\User;
use App\Services\DriverService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DriverController extends Controller
{
    public function __construct(
        protected DriverService $driverService
    ) {}

    /**
     * Display a listing of drivers.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Driver::class);

        /** @var User $currentUser */
        $currentUser = $request->user();

        $search = $request->query('search');
        $partnerId = $request->query('partner_id');
        $status = $request->query('status');

        $drivers = Driver::query()
            ->with(['referralPartner'])
            ->withCount('vehicles')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('national_id', 'like', "%{$search}%");
                });
            })
            ->when($partnerId, fn ($query, $partnerId) => $query->where('referral_partner_id', $partnerId))
            ->when($status, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Driver $driver) => [
                'id' => $driver->id,
                'name' => $driver->name,
                'phone' => $driver->phone,
                'email' => $driver->email,
                'status' => $driver->status->value,
                'vehicles_count' => $driver->vehicles_count,
                'partner' => [
                    'id' => $driver->referralPartner->id,
                    'name' => $driver->referralPartner->name,
                ],
                'created_at' => $driver->created_at?->format('Y-m-d') ?? '',
                'can' => [
                    'view' => $currentUser->can('view', $driver),
                    'edit' => $currentUser->can('update', $driver),
                    'delete' => $currentUser->can('delete', $driver),
                ],
            ]);

        $partnersList = ReferralPartner::query()
            ->orderBy('name', 'asc')
            ->get(['id', 'name']);

        return Inertia::render('Drivers/Index', [
            'drivers' => $drivers,
            'partners' => $partnersList,
            'filters' => [
                'search' => $search,
                'partner_id' => $partnerId ? (int) $partnerId : null,
                'status' => $status,
            ],
            'statuses' => DriverStatus::values(),
            'can' => [
                'create_driver' => $currentUser->can('create', Driver::class),
            ],
        ]);
    }

    /**
     * Show the form for creating a new driver.
     */
    public function create(): Response
    {
        Gate::authorize('create', Driver::class);

        $activePartners = ReferralPartner::active()
            ->orderBy('name', 'asc')
            ->get(['id', 'name']);

        return Inertia::render('Drivers/Create', [
            'partners' => $activePartners,
            'statuses' => DriverStatus::values(),
        ]);
    }

    /**
     * Store a newly created driver.
     */
    public function store(StoreDriverRequest $request): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();

        $this->driverService->createDriver($request->validated(), $currentUser);

        return redirect()->route('drivers.index')->with('success', 'Driver registered successfully.');
    }

    /**
     * Display the specified driver.
     */
    public function show(Driver $driver, Request $request): Response
    {
        Gate::authorize('view', $driver);

        /** @var User $currentUser */
        $currentUser = $request->user();

        $driver->load(['referralPartner', 'vehicles' => fn ($query) => $query->orderBy('id', 'desc')]);

        return Inertia::render('Drivers/Show', [
            'driver' => [
                'id' => $driver->id,
                'name' => $driver->name,
                'phone' => $driver->phone,
                'email' => $driver->email,
                'national_id' => $driver->national_id,
                'address' => $driver->address,
                'notes' => $driver->notes,
                'status' => $driver->status->value,
                'partner' => [
                    'id' => $driver->referralPartner->id,
                    'name' => $driver->referralPartner->name,
                    'type' => $driver->referralPartner->type->value,
                    'status' => $driver->referralPartner->status->value,
                ],
                'created_at' => $driver->created_at?->format('Y-m-d H:i') ?? '',
                'updated_at' => $driver->updated_at?->format('Y-m-d H:i') ?? '',
                'vehicles' => $driver->vehicles->map(fn ($vehicle) => [
                    'id' => $vehicle->id,
                    'plate_number' => $vehicle->plate_number,
                    'vehicle_type' => $vehicle->vehicle_type->value,
                    'make' => $vehicle->make,
                    'model' => $vehicle->model,
                    'color' => $vehicle->color,
                    'status' => $vehicle->status->value,
                    'created_at' => $vehicle->created_at?->format('Y-m-d') ?? '',
                ]),
            ],
            'can' => [
                'edit' => $currentUser->can('update', $driver),
                'delete' => $currentUser->can('delete', $driver),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified driver.
     */
    public function edit(Driver $driver): Response
    {
        Gate::authorize('update', $driver);

        $partners = ReferralPartner::query()
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'status']);

        return Inertia::render('Drivers/Edit', [
            'driver' => [
                'id' => $driver->id,
                'referral_partner_id' => $driver->referral_partner_id,
                'name' => $driver->name,
                'phone' => $driver->phone,
                'email' => $driver->email,
                'national_id' => $driver->national_id,
                'address' => $driver->address,
                'notes' => $driver->notes,
                'status' => $driver->status->value,
            ],
            'partners' => $partners,
            'statuses' => DriverStatus::values(),
        ]);
    }

    /**
     * Update the specified driver.
     */
    public function update(UpdateDriverRequest $request, Driver $driver): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();

        $this->driverService->updateDriver($driver, $request->validated(), $currentUser);

        return redirect()->route('drivers.index')->with('success', 'Driver updated successfully.');
    }

    /**
     * Remove the specified driver from storage.
     */
    public function destroy(Driver $driver, Request $request): RedirectResponse
    {
        Gate::authorize('delete', $driver);

        /** @var User $currentUser */
        $currentUser = $request->user();

        try {
            $this->driverService->deleteDriver($driver, $currentUser);
            return redirect()->route('drivers.index')->with('success', 'Driver deleted successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? 'Cannot delete driver.';
            return redirect()->back()->with('error', $message)->withErrors($e->errors());
        }
    }

    /**
     * Activate the specified driver.
     */
    public function activate(Driver $driver, Request $request): RedirectResponse
    {
        Gate::authorize('update', $driver);

        /** @var User $currentUser */
        $currentUser = $request->user();

        $this->driverService->activateDriver($driver, $currentUser);

        return redirect()->back()->with('success', 'Driver activated successfully.');
    }

    /**
     * Deactivate the specified driver.
     */
    public function deactivate(Driver $driver, Request $request): RedirectResponse
    {
        Gate::authorize('update', $driver);

        /** @var User $currentUser */
        $currentUser = $request->user();

        $this->driverService->deactivateDriver($driver, $currentUser);

        return redirect()->back()->with('success', 'Driver deactivated successfully.');
    }
}
