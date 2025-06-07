<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisterTenantController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Illuminate\Foundation\Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterTenantController::class, 'create'])->name('register');
    Route::post('/register', [RegisterTenantController::class, 'store']);

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::post('/forgot-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', function ($token) {
        return Inertia::render('Auth/ResetPassword', ['token' => $token]);
    })->name('password.reset');
});

Route::get('/invitations/{token}/accept', [\App\Http\Controllers\Tenant\TeamInvitationController::class, 'accept'])->name('invitations.accept');
Route::post('/stripe/webhook', [\App\Http\Controllers\StripeWebhookController::class, 'handleWebhook'])->name('stripe.webhook');

Route::middleware(['auth', \App\Http\Middleware\EnsureSuperAdmin::class])->group(function () {
    Route::get('/admin/impersonate/{tenant}', [\App\Http\Controllers\Admin\ImpersonateController::class, 'impersonate'])->name('admin.impersonate');
});
Route::post('/admin/impersonate/leave', [\App\Http\Controllers\Admin\ImpersonateController::class, 'leave'])->name('admin.impersonate.leave');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::post('/team/invitations', [\App\Http\Controllers\Tenant\TeamInvitationController::class, 'store'])->name('team.invitations.store');
    Route::get('/settings/team', [\App\Http\Controllers\Tenant\TeamController::class, 'index'])->name('team.index');
    Route::delete('/settings/team/{user}', [\App\Http\Controllers\Tenant\TeamController::class, 'destroy'])->name('team.destroy');

    Route::get('/settings/profile', [\App\Http\Controllers\Tenant\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/settings/profile', [\App\Http\Controllers\Tenant\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/settings/password', [\App\Http\Controllers\Tenant\ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('/billing', [\App\Http\Controllers\Tenant\BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/checkout', [\App\Http\Controllers\Tenant\BillingController::class, 'checkout'])->name('billing.checkout');
    Route::post('/billing/payment-methods', [\App\Http\Controllers\Tenant\BillingController::class, 'storePaymentMethod'])->name('billing.pm.store');

    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});
