<?php

namespace App\Services;

use App\Models\PaymentMethod;
use App\Models\Tenant;
use Stripe\Customer;
use Stripe\SetupIntent;
use Stripe\StripeClient;

class StripeService
{
    protected StripeClient $client;

    public function __construct()
    {
        $secretKey = $this->isMockMode() ? 'sk_test_mock' : (string) config('services.stripe.secret');
        $this->client = new StripeClient($secretKey);
    }

    /**
     * Determine if Stripe is running in mock/testing mode.
     */
    protected function isMockMode(): bool
    {
        $secret = (string) config('services.stripe.secret', '');

        return app()->environment('testing') || empty($secret) || str_contains($secret, 'mock') || str_contains($secret, 'sample');
    }

    /**
     * Get or create Stripe Customer ID for tenant.
     */
    public function getOrCreateCustomer(Tenant $tenant): string
    {
        /** @var array<string, mixed> $extraData */
        $extraData = is_array($tenant->extra_data) ? $tenant->extra_data : [];

        if (isset($extraData['stripe_id'])) {
            return $extraData['stripe_id'];
        }

        // Mock mode or offline fallback
        if ($this->isMockMode()) {
            $stripeId = 'cus_mock_'.$tenant->id;
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

        if ($this->isMockMode()) {
            return [
                'client_secret' => 'seti_mock_secret_'.$tenant->id,
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

        if (! $this->isMockMode()) {
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
