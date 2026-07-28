<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionBillingTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $user;

    protected Plan $proPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->proPlan = Plan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'stripe_price_id' => 'price_pro_test',
            'price_monthly' => 29.00,
            'max_users' => 25,
            'is_active' => true,
        ]);

        $this->tenant = Tenant::create([
            'name' => 'Umbrella Corp',
            'slug' => 'umbrella',
            'status' => 'active',
        ]);

        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($this->tenant);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Albert Wesker',
            'email' => 'wesker@umbrella.com',
            'password' => 'secret123',
            'role' => 'owner',
        ]);
    }

    public function test_tenant_can_checkout_subscription_plan(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeader('X-Tenant', $this->tenant->slug)
            ->post('/billing/checkout', [
                'plan_id' => $this->proPlan->id,
            ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->proPlan->id,
            'stripe_status' => 'active',
        ]);
    }

    public function test_webhook_handles_invoice_payment_succeeded(): void
    {
        $subscription = Subscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->proPlan->id,
            'type' => 'main',
            'stripe_id' => 'sub_test_123',
            'stripe_status' => 'past_due',
        ]);

        $payload = [
            'type' => 'invoice.payment_succeeded',
            'data' => [
                'object' => [
                    'subscription' => 'sub_test_123',
                ],
            ],
        ];

        $response = $this->postJson('/stripe/webhook', $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'stripe_status' => 'active',
        ]);
    }

    public function test_webhook_handles_invoice_payment_failed_grace_period(): void
    {
        $subscription = Subscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->proPlan->id,
            'type' => 'main',
            'stripe_id' => 'sub_test_456',
            'stripe_status' => 'active',
        ]);

        $payload = [
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'subscription' => 'sub_test_456',
                ],
            ],
        ];

        $response = $this->postJson('/stripe/webhook', $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'stripe_status' => 'past_due',
        ]);
    }
}
