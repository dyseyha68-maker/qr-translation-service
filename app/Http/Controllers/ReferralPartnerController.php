<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PartnerStatus;
use App\Enums\PartnerType;
use App\Http\Requests\ReferralPartner\StoreReferralPartnerRequest;
use App\Http\Requests\ReferralPartner\UpdateReferralPartnerRequest;
use App\Models\ReferralPartner;
use App\Models\User;
use App\Services\ReferralPartnerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ReferralPartnerController extends Controller
{
    public function __construct(
        protected ReferralPartnerService $partnerService
    ) {}

    /**
     * Display a listing of referral partners.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', ReferralPartner::class);

        /** @var User $currentUser */
        $currentUser = $request->user();

        $search = $request->query('search');
        $type = $request->query('type');
        $status = $request->query('status');

        $partners = ReferralPartner::query()
            ->withCount('drivers')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%");
                });
            })
            ->when($type, fn ($query, $type) => $query->where('type', $type))
            ->when($status, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (ReferralPartner $partner) => [
                'id' => $partner->id,
                'name' => $partner->name,
                'type' => $partner->type->value,
                'phone' => $partner->phone,
                'email' => $partner->email,
                'company_name' => $partner->company_name,
                'status' => $partner->status->value,
                'drivers_count' => $partner->drivers_count,
                'created_at' => $partner->created_at?->format('Y-m-d') ?? '',
                'can' => [
                    'view' => $currentUser->can('view', $partner),
                    'edit' => $currentUser->can('update', $partner),
                    'delete' => $currentUser->can('delete', $partner),
                ],
            ]);

        return Inertia::render('ReferralPartners/Index', [
            'partners' => $partners,
            'filters' => [
                'search' => $search,
                'type' => $type,
                'status' => $status,
            ],
            'types' => PartnerType::values(),
            'statuses' => PartnerStatus::values(),
            'can' => [
                'create_partner' => $currentUser->can('create', ReferralPartner::class),
            ],
        ]);
    }

    /**
     * Show the form for creating a new referral partner.
     */
    public function create(): Response
    {
        Gate::authorize('create', ReferralPartner::class);

        return Inertia::render('ReferralPartners/Create', [
            'types' => PartnerType::values(),
            'statuses' => PartnerStatus::values(),
        ]);
    }

    /**
     * Store a newly created referral partner.
     */
    public function store(StoreReferralPartnerRequest $request): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();

        $this->partnerService->createPartner($request->validated(), $currentUser);

        return redirect()->route('referral-partners.index')->with('success', 'Referral partner created successfully.');
    }

    /**
     * Display the specified referral partner.
     */
    public function show(Request $request, ReferralPartner $referralPartner): Response
    {
        Gate::authorize('view', $referralPartner);

        /** @var User $currentUser */
        $currentUser = $request->user();

        $referralPartner->load([
            'drivers' => fn ($query) => $query->withCount('vehicles')->orderBy('id', 'desc'),
        ]);

        return Inertia::render('ReferralPartners/Show', [
            'partner' => [
                'id' => $referralPartner->id,
                'name' => $referralPartner->name,
                'type' => $referralPartner->type->value,
                'phone' => $referralPartner->phone,
                'email' => $referralPartner->email,
                'company_name' => $referralPartner->company_name,
                'address' => $referralPartner->address,
                'notes' => $referralPartner->notes,
                'status' => $referralPartner->status->value,
                'created_at' => $referralPartner->created_at?->format('Y-m-d H:i') ?? '',
                'updated_at' => $referralPartner->updated_at?->format('Y-m-d H:i') ?? '',
                'drivers' => $referralPartner->drivers->map(fn ($driver) => [
                    'id' => $driver->id,
                    'name' => $driver->name,
                    'phone' => $driver->phone,
                    'status' => $driver->status->value,
                    'vehicles_count' => $driver->vehicles_count,
                    'created_at' => $driver->created_at?->format('Y-m-d') ?? '',
                ]),
            ],
            'can' => [
                'edit' => $currentUser->can('update', $referralPartner),
                'delete' => $currentUser->can('delete', $referralPartner),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified referral partner.
     */
    public function edit(ReferralPartner $referralPartner): Response
    {
        Gate::authorize('update', $referralPartner);

        return Inertia::render('ReferralPartners/Edit', [
            'partner' => [
                'id' => $referralPartner->id,
                'name' => $referralPartner->name,
                'type' => $referralPartner->type->value,
                'phone' => $referralPartner->phone,
                'email' => $referralPartner->email,
                'company_name' => $referralPartner->company_name,
                'address' => $referralPartner->address,
                'notes' => $referralPartner->notes,
                'status' => $referralPartner->status->value,
            ],
            'types' => PartnerType::values(),
            'statuses' => PartnerStatus::values(),
        ]);
    }

    /**
     * Update the specified referral partner.
     */
    public function update(UpdateReferralPartnerRequest $request, ReferralPartner $referralPartner): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();

        $this->partnerService->updatePartner($referralPartner, $request->validated(), $currentUser);

        return redirect()->route('referral-partners.index')->with('success', 'Referral partner updated successfully.');
    }

    /**
     * Remove the specified referral partner from storage.
     */
    public function destroy(Request $request, ReferralPartner $referralPartner): RedirectResponse
    {
        Gate::authorize('delete', $referralPartner);

        /** @var User $currentUser */
        $currentUser = $request->user();

        try {
            $this->partnerService->deletePartner($referralPartner, $currentUser);
            return redirect()->route('referral-partners.index')->with('success', 'Referral partner deleted successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? 'Cannot delete partner.';
            return redirect()->back()->with('error', $message)->withErrors($e->errors());
        }
    }

    /**
     * Activate the specified referral partner.
     */
    public function activate(ReferralPartner $referralPartner, Request $request): RedirectResponse
    {
        Gate::authorize('update', $referralPartner);

        /** @var User $currentUser */
        $currentUser = $request->user();

        $this->partnerService->activatePartner($referralPartner, $currentUser);

        return redirect()->back()->with('success', 'Referral partner activated successfully.');
    }

    /**
     * Deactivate the specified referral partner.
     */
    public function deactivate(ReferralPartner $referralPartner, Request $request): RedirectResponse
    {
        Gate::authorize('update', $referralPartner);

        /** @var User $currentUser */
        $currentUser = $request->user();

        $this->partnerService->deactivatePartner($referralPartner, $currentUser);

        return redirect()->back()->with('success', 'Referral partner deactivated successfully.');
    }
}
