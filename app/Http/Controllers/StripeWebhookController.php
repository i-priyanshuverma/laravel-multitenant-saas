<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends Controller
{
    /**
     * Handle incoming Stripe Webhook events.
     */
    public function handleWebhook(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true);

        if (!$payload || !isset($payload['type'])) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        $event = $payload['type'];
        $data = $payload['data']['object'] ?? [];

        switch ($event) {
            case 'invoice.payment_succeeded':
                $this->handleInvoicePaymentSucceeded($data);
                break;

            case 'invoice.payment_failed':
                $this->handleInvoicePaymentFailed($data);
                break;

            case 'customer.subscription.updated':
            case 'customer.subscription.deleted':
                $this->handleSubscriptionUpdatedOrDeleted($data);
                break;
        }

        return response()->json(['status' => 'success']);
    }

    protected function handleInvoicePaymentSucceeded(array $invoice): void
    {
        $stripeSubscriptionId = $invoice['subscription'] ?? null;
        if (!$stripeSubscriptionId) {
            return;
        }

        $subscription = Subscription::where('stripe_id', $stripeSubscriptionId)->first();
        if ($subscription) {
            $subscription->update([
                'stripe_status' => 'active',
                'ends_at' => now()->addMonth(),
            ]);

            if ($subscription->tenant) {
                $subscription->tenant->update(['status' => 'active']);
            }
        }
    }

    protected function handleInvoicePaymentFailed(array $invoice): void
    {
        $stripeSubscriptionId = $invoice['subscription'] ?? null;
        if (!$stripeSubscriptionId) {
            return;
        }

        $subscription = Subscription::where('stripe_id', $stripeSubscriptionId)->first();
        if ($subscription) {
            $subscription->update([
                'stripe_status' => 'past_due',
                'ends_at' => now()->addDays(7), // 7-day grace period
            ]);
        }
    }

    protected function handleSubscriptionUpdatedOrDeleted(array $subData): void
    {
        $stripeId = $subData['id'] ?? null;
        $status = $subData['status'] ?? 'canceled';

        if ($stripeId) {
            $subscription = Subscription::where('stripe_id', $stripeId)->first();
            if ($subscription) {
                $subscription->update([
                    'stripe_status' => $status,
                ]);

                if ($status === 'canceled' && $subscription->tenant) {
                    $subscription->tenant->update(['status' => 'suspended']);
                }
            }
        }
    }
}
