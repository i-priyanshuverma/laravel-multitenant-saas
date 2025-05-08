<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Tenant;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIdentificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_tenant_via_x_tenant_header(): void
    {
        $tenant = Tenant::create([
            'name' => 'Stark Industries',
            'slug' => 'stark',
            'status' => 'active',
        ]);

        $response = $this->withHeader('X-Tenant', $tenant->slug)
            ->get('/');

        $tenantManager = app(TenantManager::class);
        $this->assertTrue($tenantManager->hasTenant());
        $this->assertEquals($tenant->id, $tenantManager->getTenantId());
    }

    public function test_resolves_tenant_via_subdomain(): void
    {
        $tenant = Tenant::create([
            'name' => 'Wayne Enterprises',
            'slug' => 'wayne',
            'status' => 'active',
        ]);

        $response = $this->get('http://wayne.localhost/');

        $tenantManager = app(TenantManager::class);
        $this->assertTrue($tenantManager->hasTenant());
        $this->assertEquals($tenant->id, $tenantManager->getTenantId());
    }

    public function test_resolves_tenant_via_custom_domain(): void
    {
        $tenant = Tenant::create([
            'name' => 'Oscorp',
            'slug' => 'oscorp',
            'status' => 'active',
        ]);

        Domain::create([
            'tenant_id' => $tenant->id,
            'domain' => 'oscorp-tech.com',
            'is_primary' => true,
        ]);

        $response = $this->get('http://oscorp-tech.com/');

        $tenantManager = app(TenantManager::class);
        $this->assertTrue($tenantManager->hasTenant());
        $this->assertEquals($tenant->id, $tenantManager->getTenantId());
    }
}
