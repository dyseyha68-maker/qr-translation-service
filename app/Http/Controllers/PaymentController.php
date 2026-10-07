<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Payment\RecordPaymentRequest;
use App\Http\Requests\Payment\RefundPaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
    ) {}

    /**
     * Record a manual customer payment toward an order.
     */
    public function store(RecordPaymentRequest $request, Order $order): RedirectResponse
    {
        Gate::authorize('create', [Payment::class, $order]);

        /** @var User $actor */
        $actor = Auth::user();

        $payment = $this->paymentService->recordPayment(
            order: $order,
            data: $request->validated(),
            receiver: $actor
        );

        return back()->with('success', "Payment {$payment->payment_reference} of \${$payment->amount} recorded successfully.");
    }

    /**
     * Process a refund for a previously recorded customer payment.
     */
    public function refund(RefundPaymentRequest $request, Payment $payment): RedirectResponse
    {
        Gate::authorize('refund', $payment);

        /** @var User $actor */
        $actor = Auth::user();

        $refundedPayment = $this->paymentService->refundPayment(
            payment: $payment,
            refunder: $actor,
            reason: $request->validated()['refund_reason']
        );

        return back()->with('success', "Payment {$refundedPayment->payment_reference} refunded successfully.");
    }
}
