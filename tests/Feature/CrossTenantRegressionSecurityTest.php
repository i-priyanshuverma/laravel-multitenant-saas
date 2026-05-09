<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossTenantRegressionSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_a_user_cannot_access_tenant_b_workspace(): void
    {
        $tenantA = Tenant::create(['name' => 'Alpha Corp', 'slug' => 'alpha', 'status' => 'active']);
        $tenantB = Tenant::create(['name' => 'Beta Corp', 'slug' => 'beta', 'status' => 'active']);

        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);

        $tenantManager->setTenant($tenantA);
        $userA = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Alpha User',
            'email' => 'user@alpha.com',
            'password' => 'password',
            'role' => 'member',
        ]);

        $tenantManager->setTenant($tenantB);
        $userB = User::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Beta User',
            'email' => 'user@beta.com',
            'password' => 'password',
            'role' => 'member',
        ]);

        // Acting as User A under Tenant B context should be rejected or isolated
        $response = $this->actingAs($userA)
            ->withHeader('X-Tenant', $tenantB->slug)
            ->get('/settings/team');

        $response->assertStatus(200);

        // Verify team members list in view only includes tenant A or empty
        $response->assertDontSee('user@beta.com');
    }
}
