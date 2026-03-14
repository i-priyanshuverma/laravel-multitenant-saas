<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnualBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_subscribe_to_annual_billing_tier(): void
    {
        $plan = Plan::create([
            'name' => 'Enterprise Annual',
            'slug' => 'enterprise-annual',
            'price_monthly' => 99.00,
            'price_yearly' => 990.00,
            'is_active' => true,
        ]);

        $tenant = Tenant::create([
            'name' => 'Wayne Tech',
            'slug' => 'waynetech',
            'status' => 'active',
        ]);

        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($tenant);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Bruce Wayne',
            'email' => 'bruce@waynetech.com',
            'password' => 'secret123',
            'role' => 'owner',
        ]);

        $response = $this->actingAs($user)
            ->withHeader('X-Tenant', $tenant->slug)
            ->post('/billing/checkout', [
                'plan_id' => $plan->id,
                'cycle' => 'yearly',
            ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'type' => 'yearly',
            'stripe_status' => 'active',
        ]);
    }
}
