<?php

use App\Http\Controllers\Admin\ImpersonateController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterTenantController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\Tenant\BillingController;
use App\Http\Controllers\Tenant\ProfileController;
use App\Http\Controllers\Tenant\TeamController;
use App\Http\Controllers\Tenant\TeamInvitationController;
use App\Http\Middleware\EnsureSuperAdmin;
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

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterTenantController::class, 'create'])->name('register');
    Route::post('/register', [RegisterTenantController::class, 'store']);

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', function ($token) {
        return Inertia::render('Auth/ResetPassword', ['token' => $token]);
    })->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});

Route::get('/invitations/{token}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])->name('stripe.webhook');

Route::middleware(['auth', EnsureSuperAdmin::class])->group(function () {
    Route::get('/admin/impersonate/{tenant}', [ImpersonateController::class, 'impersonate'])->name('admin.impersonate');
});
Route::post('/admin/impersonate/leave', [ImpersonateController::class, 'leave'])->name('admin.impersonate.leave');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::post('/team/invitations', [TeamInvitationController::class, 'store'])->middleware('plan.limits:users')->name('team.invitations.store');
    Route::get('/settings/team', [TeamController::class, 'index'])->name('team.index');
    Route::delete('/settings/team/{user}', [TeamController::class, 'destroy'])->name('team.destroy');

    Route::get('/settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/settings/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::post('/billing/payment-methods', [BillingController::class, 'storePaymentMethod'])->name('billing.pm.store');

    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});
