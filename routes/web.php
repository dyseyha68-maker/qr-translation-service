<?php

declare(strict_types=1);

use App\Http\Controllers\DriverController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReferralPartnerController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use App\Models\Driver;
use App\Models\ReferralPartner;
use App\Models\Vehicle;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard', [
        'activePartnersCount' => ReferralPartner::active()->count(),
        'activeDriversCount' => Driver::active()->count(),
        'activeVehiclesCount' => Vehicle::active()->count(),
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
});

require __DIR__.'/auth.php';
