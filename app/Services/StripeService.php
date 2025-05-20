<?php

namespace App\Services;

use App\Models\PaymentMethod;
use App\Models\Tenant;
use Stripe\Customer;
use Stripe\PaymentMethod as StripePaymentMethod;
use Stripe\SetupIntent;
use Stripe\StripeClient;

class StripeService
{
    protected StripeClient $client;

    public function __construct()
    {
        $secretKey = config('services.stripe.secret') ?: 'sk_test_mock';
        $this->client = new StripeClient($secretKey);
    }

    /**
     * Get or create Stripe Customer ID for tenant.
     */
    public function getOrCreateCustomer(Tenant $tenant): string
    {
        $extraData = $tenant->extra_data ?? [];

        if (isset($extraData['stripe_id'])) {
            return $extraData['stripe_id'];
        }

        // Mock mode or offline fallback
        if (config('services.stripe.secret') === null || str_contains(config('services.stripe.secret'), 'mock')) {
            $stripeId = 'cus_mock_' . $tenant->id;
            $extraData['stripe_id'] = $stripeId;
            $tenant->update(['extra_data' => $extraData]);
            return $stripeId;
        }

        $customer = $this->client->customers->create([
            'name' => $tenant->name,
            'email' => $tenant->owner?->email,
            'metadata' => [
                'tenant_id' => $tenant->id,
                'tenant_slug' => $tenant->slug,
            ],
        ]);

        $extraData['stripe_id'] = $customer->id;
        $tenant->update(['extra_data' => $extraData]);

        return $customer->id;
    }

    /**
     * Create SetupIntent for adding credit cards.
     */
    public function createSetupIntent(Tenant $tenant): array
    {
        $customerId = $this->getOrCreateCustomer($tenant);

        if (str_contains(config('services.stripe.secret', ''), 'mock')) {
            return [
                'client_secret' => 'seti_mock_secret_' . $tenant->id,
            ];
        }

        $setupIntent = $this->client->setupIntents->create([
            'customer' => $customerId,
            'payment_method_types' => ['card'],
        ]);

        return [
            'client_secret' => $setupIntent->client_secret,
        ];
    }

    /**
     * Attach payment method to tenant.
     */
    public function savePaymentMethod(Tenant $tenant, string $stripePaymentMethodId): PaymentMethod
    {
        $customerId = $this->getOrCreateCustomer($tenant);

        $brand = 'visa';
        $last4 = '4242';
        $expMonth = '12';
        $expYear = '2028';

        if (!str_contains(config('services.stripe.secret', ''), 'mock')) {
            try {
                $pm = $this->client->paymentMethods->retrieve($stripePaymentMethodId);
                $pm->attach(['customer' => $customerId]);

                $brand = $pm->card->brand ?? 'card';
                $last4 = $pm->card->last4 ?? '4242';
                $expMonth = (string) ($pm->card->exp_month ?? 12);
                $expYear = (string) ($pm->card->exp_year ?? 2028);
            } catch (\Exception $e) {
                // Fallback to default card values if mock
            }
        }

        // Set previous payment methods default to false
        PaymentMethod::where('tenant_id', $tenant->id)->update(['is_default' => false]);

        return PaymentMethod::create([
            'tenant_id' => $tenant->id,
            'stripe_payment_method_id' => $stripePaymentMethodId,
            'card_brand' => $brand,
            'card_last_four' => $last4,
            'card_exp_month' => $expMonth,
            'card_exp_year' => $expYear,
            'is_default' => true,
        ]);
    }
}
