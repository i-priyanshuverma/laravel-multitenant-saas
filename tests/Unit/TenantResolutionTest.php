<?php

namespace Tests\Unit;

use App\Models\Domain;
use App\Models\Tenant;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected TenantManager $tenantManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantManager = app(TenantManager::class);
    }

    public function test_tenant_manager_can_set_and_get_tenant(): void
    {
        $tenant = Tenant::create([
            'name' => 'Acme Corp',
            'slug' => 'acme',
            'status' => 'active',
        ]);

        $this->tenantManager->setTenant($tenant);

        $this->assertTrue($this->tenantManager->hasTenant());
        $this->assertEquals($tenant->id, $this->tenantManager->getTenantId());
        $this->assertEquals('Acme Corp', $this->tenantManager->getTenant()->name);

        $this->tenantManager->forgetTenant();
        $this->assertFalse($this->tenantManager->hasTenant());
        $this->assertNull($this->tenantManager->getTenant());
    }
}
