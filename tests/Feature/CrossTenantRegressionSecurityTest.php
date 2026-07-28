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

        // Querying users under Tenant A context must never return Tenant B users
        $tenantManager->setTenant($tenantA);
        $alphaUsers = User::all();

        $this->assertTrue($alphaUsers->contains($userA));
        $this->assertFalse($alphaUsers->contains($userB));
        $this->assertEquals(1, $alphaUsers->count());
    }
}
