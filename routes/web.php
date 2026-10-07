<?php

declare(strict_types=1);

use App\Http\Controllers\DriverController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReferralCodeController;
use App\Http\Controllers\ReferralPartnerController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use App\Models\Driver;
use App\Models\ReferralCode;
use App\Models\ReferralPartner;
use App\Models\Vehicle;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\LeadController;
use App\Models\Lead;
use App\Enums\LeadStatus;

use App\Enums\OrderStatus;
use App\Enums\PaymentRecordStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Models\Order;
use App\Models\Payment;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    $orders = Order::with('payments')->get();
    $totalPaid = (float) Payment::where('status', PaymentRecordStatus::PAID->value)->sum('amount');
    $totalOutstanding = (float) $orders->whereIn('status', [OrderStatus::PENDING, OrderStatus::CONFIRMED])
        ->sum(fn (Order $o) => $o->outstanding_amount);

    return Inertia::render('Dashboard', [
        'activePartnersCount' => ReferralPartner::active()->count(),
        'activeDriversCount' => Driver::active()->count(),
        'activeVehiclesCount' => Vehicle::active()->count(),
        'activeReferralCodesCount' => ReferralCode::active()->count(),
        'newLeadsCount' => Lead::where('status', LeadStatus::NEW)->count(),
        'activeLeadsCount' => Lead::active()->count(),
        'ordersSummary' => [
            'newOrders' => $orders->where('status', OrderStatus::PENDING)->count(),
            'confirmedOrders' => $orders->where('status', OrderStatus::CONFIRMED)->count(),
            'completedOrders' => $orders->where('status', OrderStatus::COMPLETED)->count(),
            'cancelledOrders' => $orders->where('status', OrderStatus::CANCELLED)->count(),
            'unpaidOrders' => $orders->where('payment_status', PaymentStatus::UNPAID)->count(),
            'partiallyPaidOrders' => $orders->where('payment_status', PaymentStatus::PARTIAL)->count(),
            'fullyPaidOrders' => $orders->where('payment_status', PaymentStatus::PAID)->count(),
            'totalPaidAmount' => round($totalPaid, 2),
            'totalOutstandingAmount' => round($totalOutstanding, 2),
        ],
    ]);
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('users', UserController::class)->except(['show']);

    Route::patch('referral-partners/{referral_partner}/activate', [ReferralPartnerController::class, 'activate'])->name('referral-partners.activate');
    Route::patch('referral-partners/{referral_partner}/deactivate', [ReferralPartnerController::class, 'deactivate'])->name('referral-partners.deactivate');
    Route::resource('referral-partners', ReferralPartnerController::class);

    Route::patch('drivers/{driver}/activate', [DriverController::class, 'activate'])->name('drivers.activate');
    Route::patch('drivers/{driver}/deactivate', [DriverController::class, 'deactivate'])->name('drivers.deactivate');
    Route::resource('drivers', DriverController::class);

    Route::patch('vehicles/{vehicle}/activate', [VehicleController::class, 'activate'])->name('vehicles.activate');
    Route::patch('vehicles/{vehicle}/deactivate', [VehicleController::class, 'deactivate'])->name('vehicles.deactivate');
    Route::resource('vehicles', VehicleController::class);

    Route::patch('referral-codes/{referral_code}/activate', [ReferralCodeController::class, 'activate'])->name('referral-codes.activate');
    Route::patch('referral-codes/{referral_code}/deactivate', [ReferralCodeController::class, 'deactivate'])->name('referral-codes.deactivate');
    Route::get('referral-codes/{referral_code}/qr', [ReferralCodeController::class, 'qr'])->name('referral-codes.qr');
    Route::get('referral-codes/{referral_code}/download', [ReferralCodeController::class, 'download'])->name('referral-codes.download');
    Route::get('referral-codes/{referral_code}/print', [ReferralCodeController::class, 'print'])->name('referral-codes.print');
    Route::resource('referral-codes', ReferralCodeController::class);

    // Phase 4: Lead Management & Attribution
    Route::get('leads', [LeadController::class, 'index'])->name('leads.index');
    Route::get('leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
    Route::patch('leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
    Route::post('leads/{lead}/assign', [LeadController::class, 'assign'])->name('leads.assign');
    Route::post('leads/{lead}/notes', [LeadController::class, 'addNote'])->name('leads.notes.store');
    Route::post('leads/{lead}/change-attribution', [LeadController::class, 'changeAttribution'])->name('leads.change-attribution');

    // Phase 5: Orders & Customer Payment Tracking
    Route::patch('orders/{order}/confirm', [OrderController::class, 'confirm'])->name('orders.confirm');
    Route::patch('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::patch('orders/{order}/complete', [OrderController::class, 'complete'])->name('orders.complete');
    Route::post('orders/{order}/payments', [PaymentController::class, 'store'])->name('orders.payments.store');
    Route::patch('payments/{payment}/refund', [PaymentController::class, 'refund'])->name('payments.refund');
    Route::resource('orders', OrderController::class);
});

require __DIR__.'/auth.php';
