<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function __construct(protected StripeService $stripeService)
    {
    }

    /**
     * Subscribe or upgrade a tenant to a plan.
     */
    public function subscribe(Tenant $tenant, Plan $plan): Subscription
    {
        return DB::transaction(function () use ($tenant, $plan) {
            // Cancel existing active or trialing subscriptions
            Subscription::where('tenant_id', $tenant->id)
                ->whereIn('stripe_status', ['active', 'trialing', 'past_due'])
                ->update(['stripe_status' => 'canceled', 'ends_at' => now()]);

            // Create new active subscription
            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'type' => 'main',
                'stripe_id' => 'sub_live_' . uniqid(),
                'stripe_status' => 'active',
                'stripe_price' => $plan->stripe_price_id,
                'quantity' => 1,
                'ends_at' => now()->addMonth(),
            ]);

            // Sync plan to tenant record
            $tenant->update(['plan_id' => $plan->id]);

            return $subscription;
        });
    }

    /**
     * Cancel an active subscription.
     */
    public function cancel(Subscription $subscription): void
    {
        $subscription->update([
            'stripe_status' => 'canceled',
            'ends_at' => now(),
        ]);
    }
}
