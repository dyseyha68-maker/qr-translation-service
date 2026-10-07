<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ReferralPartner;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
    ) {}

    /**
     * Display a paginated listing of orders with filters.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Order::class);

        $query = Order::query()
            ->with([
                'customer',
                'lead',
                'referralAttribution.referralPartner',
                'referralAttribution.driver',
                'payments',
            ])
            ->search($request->input('search'))
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->input('status'));
            })
            ->wherePaymentStatus($request->input('payment_status'))
            ->when($request->filled('partner_id'), function ($q) use ($request) {
                $q->whereHas('referralAttribution', function ($attrQ) use ($request) {
                    $attrQ->where('referral_partner_id', $request->input('partner_id'));
                });
            })
            ->when($request->filled('date_from'), function ($q) use ($request) {
                $q->whereDate('created_at', '>=', $request->input('date_from'));
            })
            ->when($request->filled('date_to'), function ($q) use ($request) {
                $q->whereDate('created_at', '<=', $request->input('date_to'));
            })
            ->latest('id');

        $orders = $query->paginate(15)->withQueryString();

        $partners = ReferralPartner::active()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', ''),
                'payment_status' => $request->input('payment_status', ''),
                'partner_id' => $request->input('partner_id', ''),
                'date_from' => $request->input('date_from', ''),
                'date_to' => $request->input('date_to', ''),
            ],
            'statuses' => OrderStatus::values(),
            'paymentStatuses' => PaymentStatus::values(),
            'partners' => $partners,
        ]);
    }

    /**
     * Show the form for creating a new order from a lead.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        Gate::authorize('create', Order::class);

        $leadId = $request->query('lead_id');
        if (! $leadId) {
            return redirect()->route('leads.index')->with('error', 'A lead is required to create an order.');
        }

        /** @var Lead $lead */
        $lead = Lead::with([
            'customer',
            'referralAttribution.referralPartner',
            'referralAttribution.driver',
            'referralAttribution.vehicle',
            'referralAttribution.referralCode',
            'order',
        ])->findOrFail($leadId);

        if ($lead->order && $lead->order->status !== OrderStatus::CANCELLED) {
            return redirect()->route('orders.show', $lead->order)
                ->with('warning', 'An active order already exists for this lead.');
        }

        return Inertia::render('Orders/Create', [
            'lead' => $lead,
        ]);
    }

    /**
     * Store a newly created order from a lead.
     */
    public function store(StoreOrderRequest $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = Auth::user();

        /** @var Lead $lead */
        $lead = Lead::findOrFail($request->validated()['lead_id']);

        $order = $this->orderService->createFromLead($lead, $request->validated(), $actor);

        return redirect()->route('orders.show', $order)
            ->with('success', "Order {$order->order_number} created successfully.");
    }

    /**
     * Display the specified order with full attribution and payment history.
     */
    public function show(Order $order): Response
    {
        Gate::authorize('view', $order);

        $order->load([
            'customer',
            'lead',
            'referralAttribution.referralCode',
            'referralAttribution.referralPartner',
            'referralAttribution.driver',
            'referralAttribution.vehicle',
            'items',
            'payments.receiver',
            'payments.refunder',
            'commission',
            'creator',
            'updater',
        ]);

        /** @var User $currentUser */
        $currentUser = Auth::user();

        return Inertia::render('Orders/Show', [
            'order' => $order,
            'paymentMethods' => PaymentMethod::values(),
            'can' => [
                'update' => $currentUser->can('update', $order),
                'confirm' => $currentUser->can('confirm', $order),
                'cancel' => $currentUser->can('cancel', $order),
                'complete' => $currentUser->can('complete', $order),
                'delete' => $currentUser->can('delete', $order),
                'recordPayment' => $currentUser->can('create', [Payment::class, $order]),
                'refundPayment' => $currentUser->can('refund', Payment::class),
            ],
        ]);
    }

    /**
     * Update the specified order.
     */
    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        Gate::authorize('update', $order);

        /** @var User $actor */
        $actor = Auth::user();

        $this->orderService->update($order, $request->validated(), $actor);

        return back()->with('success', 'Order updated successfully.');
    }

    /**
     * Confirm a pending order.
     */
    public function confirm(Order $order): RedirectResponse
    {
        Gate::authorize('confirm', $order);

        /** @var User $actor */
        $actor = Auth::user();

        $this->orderService->confirm($order, $actor);

        return back()->with('success', 'Order confirmed successfully.');
    }

    /**
     * Cancel an order.
     */
    public function cancel(Request $request, Order $order): RedirectResponse
    {
        Gate::authorize('cancel', $order);

        /** @var User $actor */
        $actor = Auth::user();

        $this->orderService->cancel($order, $actor, $request->input('reason'));

        return back()->with('success', 'Order cancelled successfully.');
    }

    /**
     * Mark a confirmed order as completed.
     */
    public function complete(Order $order): RedirectResponse
    {
        Gate::authorize('complete', $order);

        /** @var User $actor */
        $actor = Auth::user();

        $this->orderService->complete($order, $actor);

        return back()->with('success', 'Order marked as completed.');
    }

    /**
     * Delete an un-invoiced/unpaid order if permitted.
     */
    public function destroy(Order $order): RedirectResponse
    {
        Gate::authorize('delete', $order);

        /** @var User $actor */
        $actor = Auth::user();

        $this->orderService->delete($order, $actor);

        return redirect()->route('orders.index')->with('success', 'Order deleted successfully.');
    }
}
