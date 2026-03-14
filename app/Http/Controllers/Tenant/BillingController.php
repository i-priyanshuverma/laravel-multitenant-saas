<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\StripeService;
use App\Services\SubscriptionService;
use App\Services\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function __construct(
        protected StripeService $stripeService,
        protected SubscriptionService $subscriptionService
    ) {
    }

    /**
     * Display subscription plans and payment methods.
     */
    public function index(): Response
    {
        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);
        $tenant = $tenantManager->getTenant();

        $plans = Plan::where('is_active', true)->get();
        $subscription = $tenant?->activeSubscription;
        $paymentMethods = $tenant?->paymentMethods;
        $setupIntent = $tenant ? $this->stripeService->createSetupIntent($tenant) : null;

        return Inertia::render('Billing/Index', [
            'plans' => $plans,
            'subscription' => $subscription ? [
                'id' => $subscription->id,
                'plan_id' => $subscription->plan_id,
                'stripe_status' => $subscription->stripe_status,
                'ends_at' => $subscription->ends_at?->format('M d, Y'),
            ] : null,
            'paymentMethods' => $paymentMethods,
            'setupIntent' => $setupIntent,
        ]);
    }

    /**
     * Handle checkout / plan upgrade via SubscriptionService.
     */
    public function checkout(Request $request): RedirectResponse
    {
        $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'cycle' => ['nullable', 'string', 'in:monthly,yearly'],
        ]);

        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);
        $tenant = $tenantManager->getTenant();

        /** @var Plan $plan */
        $plan = Plan::findOrFail($request->plan_id);
        $cycle = $request->input('cycle', 'monthly');

        if ($tenant) {
            $this->subscriptionService->subscribe($tenant, $plan, $cycle);
        }

        return back()->with('success', 'Successfully subscribed to the ' . $plan->name . ' ' . $cycle . ' plan!');
    }

    /**
     * Store new payment method for tenant.
     */
    public function storePaymentMethod(Request $request): RedirectResponse
    {
        $request->validate([
            'payment_method_id' => ['required', 'string'],
        ]);

        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);
        $tenant = $tenantManager->getTenant();

        $this->stripeService->savePaymentMethod($tenant, $request->payment_method_id);

        return back()->with('success', 'Payment method saved successfully.');
    }
}
