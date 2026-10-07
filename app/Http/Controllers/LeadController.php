<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\LeadStatus;
use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Http\Requests\Lead\AddLeadNoteRequest;
use App\Http\Requests\Lead\AssignLeadRequest;
use App\Http\Requests\Lead\ChangeAttributionRequest;
use App\Http\Requests\Lead\UpdateLeadRequest;
use App\Models\Lead;
use App\Models\ReferralCode;
use App\Models\User;
use App\Services\ReferralAttributionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    public function __construct(
        protected ReferralAttributionService $attributionService,
    ) {}

    /**
     * Display a paginated listing of leads with filters.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Lead::class);

        $query = Lead::query()
            ->with([
                'customer',
                'telegramUser',
                'assignedUser',
                'referralAttribution.referralCode',
                'referralAttribution.driver',
                'referralAttribution.vehicle',
            ])
            ->search($request->input('search'))
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->input('status'));
            })
            ->latest('id');

        $leads = $query->paginate(15)->withQueryString();

        return Inertia::render('Leads/Index', [
            'leads' => $leads,
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', ''),
            ],
            'statuses' => LeadStatus::values(),
        ]);
    }

    /**
     * Display the specified lead.
     */
    public function show(Lead $lead): Response
    {
        Gate::authorize('view', $lead);

        $lead->load([
            'customer',
            'telegramUser',
            'assignedUser',
            'referralAttribution.referralCode',
            'referralAttribution.driver',
            'referralAttribution.vehicle',
            'referralAttribution.referralPartner',
            'attributions.referralCode',
            'attributions.driver',
            'attributions.vehicle',
            'attributions.creator',
            'notes.user',
            'order.payments',
            'order.items',
        ]);

        /** @var User $currentUser */
        $currentUser = Auth::user();

        // Active referral codes available for attribution change (only if user has permission)
        $canChangeAttribution = $currentUser->can('changeAttribution', $lead);
        $availableReferralCodes = $canChangeAttribution
            ? ReferralCode::active()->with(['driver', 'vehicle'])->get()
            : [];

        // Assignable staff members
        $assignableUsers = User::active()->get(['id', 'name', 'email']);

        return Inertia::render('Leads/Show', [
            'lead' => $lead,
            'statuses' => LeadStatus::values(),
            'assignableUsers' => $assignableUsers,
            'availableReferralCodes' => $availableReferralCodes,
            'can' => [
                'update' => $currentUser->can('update', $lead),
                'assign' => $currentUser->can('assign', $lead),
                'addNote' => $currentUser->can('addNote', $lead),
                'changeAttribution' => $canChangeAttribution,
                'createOrder' => $currentUser->can('orders.create'),
            ],
        ]);
    }

    /**
     * Update the specified lead's basic operational details.
     */
    public function update(UpdateLeadRequest $request, Lead $lead): RedirectResponse
    {
        $lead->update($request->validated());

        return back()->with('success', 'Lead details updated successfully.');
    }

    /**
     * Assign the lead to a staff member.
     */
    public function assign(AssignLeadRequest $request, Lead $lead): RedirectResponse
    {
        $assignedTo = $request->validated()['assigned_to'] ?? null;
        $lead->update(['assigned_to' => $assignedTo]);

        $assigneeName = $assignedTo ? User::find($assignedTo)?->name : 'Unassigned';
        $lead->notes()->create([
            'user_id' => Auth::id(),
            'note' => "Lead assigned to {$assigneeName}.",
        ]);

        return back()->with('success', "Lead assigned to {$assigneeName}.");
    }

    /**
     * Add an operational staff note to the lead.
     */
    public function addNote(AddLeadNoteRequest $request, Lead $lead): RedirectResponse
    {
        $lead->notes()->create([
            'user_id' => Auth::id(),
            'note' => $request->validated()['note'],
        ]);

        return back()->with('success', 'Note added successfully.');
    }

    /**
     * Manually change attribution of the lead.
     */
    public function changeAttribution(ChangeAttributionRequest $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validated();
        /** @var ReferralCode $newReferralCode */
        $newReferralCode = ReferralCode::findOrFail($validated['referral_code_id']);

        /** @var User $actor */
        $actor = Auth::user();

        $this->attributionService->changeAttribution(
            lead: $lead,
            newReferralCode: $newReferralCode,
            actor: $actor,
            reason: $validated['reason']
        );

        return back()->with('success', 'Lead attribution changed successfully.');
    }
}
