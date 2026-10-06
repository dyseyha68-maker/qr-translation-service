<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ReferralCodeStatus;
use App\Http\Requests\ReferralCode\StoreReferralCodeRequest;
use App\Http\Requests\ReferralCode\UpdateReferralCodeRequest;
use App\Models\Driver;
use App\Models\ReferralCode;
use App\Models\ReferralPartner;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\QrCodeService;
use App\Services\ReferralCodeService;
use App\Services\TelegramReferralLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ReferralCodeController extends Controller
{
    public function __construct(
        protected ReferralCodeService $referralCodeService,
        protected TelegramReferralLinkService $linkService,
        protected QrCodeService $qrCodeService
    ) {}

    /**
     * Display a paginated listing of referral codes.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', ReferralCode::class);

        $search = $request->string('search')->trim()->value();
        $status = $request->string('status')->trim()->value();
        $partnerId = $request->input('partner_id');
        $driverId = $request->input('driver_id');
        $vehicleType = $request->string('vehicle_type')->trim()->value();

        $query = ReferralCode::query()
            ->with([
                'referralPartner:id,name,type',
                'driver:id,name,phone',
                'vehicle:id,driver_id,plate_number,vehicle_type,make,model',
            ]);

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('label', 'like', "%{$search}%")
                    ->orWhereHas('referralPartner', fn ($p) => $p->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('driver', fn ($d) => $d->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))
                    ->orWhereHas('vehicle', fn ($v) => $v->where('plate_number', 'like', "%{$search}%"));
            });
        }

        if (! empty($status)) {
            $query->where('status', $status);
        }

        if (! empty($partnerId)) {
            $query->where('referral_partner_id', (int) $partnerId);
        }

        if (! empty($driverId)) {
            $query->where('driver_id', (int) $driverId);
        }

        if (! empty($vehicleType)) {
            $query->whereHas('vehicle', fn ($v) => $v->where('vehicle_type', $vehicleType));
        }

        $referralCodes = $query->latest('id')->paginate(15)->withQueryString();

        return Inertia::render('ReferralCodes/Index', [
            'referralCodes' => $referralCodes,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'partner_id' => $partnerId,
                'driver_id' => $driverId,
                'vehicle_type' => $vehicleType,
            ],
            'partners' => ReferralPartner::select('id', 'name')->orderBy('name')->get(),
            'drivers' => Driver::select('id', 'name')->orderBy('name')->get(),
            'statuses' => ReferralCodeStatus::values(),
        ]);
    }

    /**
     * Show the form for creating a new referral code.
     */
    public function create(): Response
    {
        Gate::authorize('create', ReferralCode::class);

        // Retrieve active partners with active drivers and their active vehicles for cascading selects
        $partners = ReferralPartner::query()
            ->where('status', 'ACTIVE')
            ->with([
                'drivers' => function ($query) {
                    $query->where('status', 'ACTIVE')
                        ->with([
                            'vehicles' => fn ($vq) => $vq->where('status', 'ACTIVE'),
                        ]);
                },
            ])
            ->orderBy('name')
            ->get();

        return Inertia::render('ReferralCodes/Create', [
            'partners' => $partners,
            'suggestedCode' => $this->referralCodeService->generateUniqueCode(),
            'statuses' => ReferralCodeStatus::values(),
        ]);
    }

    /**
     * Store a newly created referral code.
     */
    public function store(StoreReferralCodeRequest $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $referralCode = $this->referralCodeService->create($request->validated(), $actor);

        return redirect()->route('referral-codes.show', $referralCode->id)
            ->with('success', "Referral code '{$referralCode->code}' created successfully.");
    }

    /**
     * Display the specified referral code with QR preview and deep link.
     */
    public function show(ReferralCode $referralCode): Response
    {
        Gate::authorize('view', $referralCode);

        $referralCode->load([
            'referralPartner',
            'driver',
            'vehicle',
            'creator:id,name,email',
        ]);

        $telegramDeepLink = $this->linkService->buildForReferralCode($referralCode);
        $qrSvg = $this->qrCodeService->generateForReferralCode($referralCode, 300);

        return Inertia::render('ReferralCodes/Show', [
            'referralCode' => $referralCode,
            'telegramDeepLink' => $telegramDeepLink,
            'qrSvg' => $qrSvg,
        ]);
    }

    /**
     * Show the form for editing metadata (label, notes) of a referral code.
     */
    public function edit(ReferralCode $referralCode): Response
    {
        Gate::authorize('update', $referralCode);

        $referralCode->load(['referralPartner', 'driver', 'vehicle']);

        return Inertia::render('ReferralCodes/Edit', [
            'referralCode' => $referralCode,
        ]);
    }

    /**
     * Update metadata for the specified referral code.
     */
    public function update(UpdateReferralCodeRequest $request, ReferralCode $referralCode): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $this->referralCodeService->updateMetadata($referralCode, $request->validated(), $actor);

        return redirect()->route('referral-codes.show', $referralCode->id)
            ->with('success', 'Referral code metadata updated successfully.');
    }

    /**
     * Activate the specified referral code.
     */
    public function activate(ReferralCode $referralCode, Request $request): RedirectResponse
    {
        Gate::authorize('activate', $referralCode);

        /** @var User $actor */
        $actor = $request->user();

        try {
            $this->referralCodeService->activate($referralCode, $actor);
            return redirect()->back()->with('success', "Referral code '{$referralCode->code}' activated successfully.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? 'Cannot activate code.';
            return redirect()->back()->with('error', $message)->withErrors($e->errors());
        }
    }

    /**
     * Deactivate the specified referral code.
     */
    public function deactivate(ReferralCode $referralCode, Request $request): RedirectResponse
    {
        Gate::authorize('deactivate', $referralCode);

        /** @var User $actor */
        $actor = $request->user();

        $this->referralCodeService->deactivate($referralCode, $actor);

        return redirect()->back()->with('success', "Referral code '{$referralCode->code}' deactivated successfully.");
    }

    /**
     * Remove the specified referral code from storage.
     */
    public function destroy(ReferralCode $referralCode, Request $request): RedirectResponse
    {
        Gate::authorize('delete', $referralCode);

        /** @var User $actor */
        $actor = $request->user();

        try {
            $this->referralCodeService->delete($referralCode, $actor);
            return redirect()->route('referral-codes.index')
                ->with('success', 'Referral code removed successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? 'Cannot delete code.';
            return redirect()->back()->with('error', $message)->withErrors($e->errors());
        }
    }

    /**
     * Stream SVG QR code directly.
     */
    public function qr(ReferralCode $referralCode): HttpResponse
    {
        Gate::authorize('viewQr', $referralCode);

        $svg = $this->qrCodeService->generateForReferralCode($referralCode, 350);

        return new HttpResponse($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'no-cache, private',
        ]);
    }

    /**
     * Download the referral QR code as a file.
     */
    public function download(ReferralCode $referralCode): HttpResponse
    {
        Gate::authorize('downloadQr', $referralCode);

        return $this->qrCodeService->downloadResponse($referralCode, 500);
    }

    /**
     * Display a print-ready promotional flyer/card layout for taxi/tuk-tuk mounting.
     */
    public function print(ReferralCode $referralCode): Response
    {
        Gate::authorize('viewQr', $referralCode);

        $referralCode->load(['referralPartner', 'driver', 'vehicle']);
        $telegramDeepLink = $this->linkService->buildForReferralCode($referralCode);
        $qrSvg = $this->qrCodeService->generateForReferralCode($referralCode, 400);

        return Inertia::render('ReferralCodes/Print', [
            'referralCode' => $referralCode,
            'telegramDeepLink' => $telegramDeepLink,
            'qrSvg' => $qrSvg,
        ]);
    }
}
