<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CheckoutRequest;
use App\Http\Requests\Tenant\StorePaymentMethodRequest;
use App\Models\Plan;
use App\Services\StripeService;
use App\Services\SubscriptionService;
use App\Services\TenantManager;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function __construct(
        protected TenantManager $tenantManager,
        protected StripeService $stripeService,
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Display subscription plans and payment methods.
     */
    public function index(): Response
    {
        $tenant = $this->tenantManager->getTenant();

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
    public function checkout(CheckoutRequest $request): RedirectResponse
    {
        $tenant = $this->tenantManager->getTenant();

        /** @var Plan $plan */
        $plan = Plan::findOrFail($request->validated('plan_id'));
        $cycle = (string) $request->input('cycle', 'monthly');

        if ($tenant) {
            $this->subscriptionService->subscribe($tenant, $plan, $cycle);
        }

        return back()->with('success', 'Successfully subscribed to the '.$plan->name.' '.$cycle.' plan!');
    }

    /**
     * Store new payment method for tenant.
     */
    public function storePaymentMethod(StorePaymentMethodRequest $request): RedirectResponse
    {
        $tenant = $this->tenantManager->getTenant();

        if ($tenant) {
            $this->stripeService->savePaymentMethod($tenant, (string) $request->validated('payment_method_id'));
        }

        return back()->with('success', 'Payment method saved successfully.');
    }
}
