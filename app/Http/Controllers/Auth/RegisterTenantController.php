<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\TenantWelcomeMail;
use App\Models\Domain;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisterTenantController extends Controller
{
    /**
     * Display the tenant onboarding & user registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'plans' => Plan::where('is_active', true)->get(),
        ]);
    }

    /**
     * Handle tenant onboarding and owner registration.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_slug' => ['required', 'string', 'max:63', 'alpha_dash', 'unique:tenants,slug'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($request) {
            // 1. Create Tenant
            $tenant = Tenant::create([
                'name' => $request->company_name,
                'slug' => strtolower($request->company_slug),
                'status' => 'active',
                'trial_ends_at' => now()->addDays(14),
            ]);

            // 2. Create primary domain for tenant
            $appHost = parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';
            $domain = strtolower($request->company_slug).'.'.$appHost;

            Domain::create([
                'tenant_id' => $tenant->id,
                'domain' => $domain,
                'is_primary' => true,
            ]);

            // 3. Set Tenant Context to allow user creation under this tenant
            /** @var TenantManager $tenantManager */
            $tenantManager = app(TenantManager::class);
            $tenantManager->setTenant($tenant);

            // 4. Create Owner User
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'owner',
                'is_super_admin' => false,
            ]);

            // 5. Assign owner_id to tenant
            $tenant->update(['owner_id' => $user->id]);

            // 6. Send welcome email notification
            try {
                Mail::to($user)->queue(new TenantWelcomeMail($tenant, $user));
            } catch (\Throwable $e) {
                // Ignore mail queue exceptions in local/testing environment
            }

            return $user;
        });

        Auth::login($user);

        return redirect()->intended('/dashboard')->with('success', 'Tenant onboarding completed successfully! Welcome to your workspace.');
    }
}
