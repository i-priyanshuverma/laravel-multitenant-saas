<?php

namespace App\Http\Controllers\Auth;

use App\Actions\OnboardTenantAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterTenantRequest;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisterTenantController extends Controller
{
    public function __construct(
        protected OnboardTenantAction $onboardTenantAction
    ) {}

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
    public function store(RegisterTenantRequest $request): RedirectResponse
    {
        /** @var array{company_name: string, company_slug: string, name: string, email: string, password: string} $validated */
        $validated = $request->validated();

        $user = $this->onboardTenantAction->execute($validated);

        Auth::login($user);

        return redirect()->intended('/dashboard')->with('success', 'Tenant onboarding completed successfully! Welcome to your workspace.');
    }
}
