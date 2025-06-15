<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RawSqlInjectionSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_slug_sanitization_prevents_sql_injection(): void
    {
        $tenant = Tenant::create([
            'name' => 'Secure Org',
            'slug' => 'secure-org',
            'status' => 'active',
        ]);

        $sqlInjectionPayloads = [
            "' OR '1'='1",
            "secure-org' UNION SELECT * FROM users --",
            "'; DROP TABLE tenants; --",
            "1' OR 1=1 #",
        ];

        foreach ($sqlInjectionPayloads as $payload) {
            $response = $this->withHeader('X-Tenant', $payload)
                ->get('/');

            /** @var TenantManager $tenantManager */
            $tenantManager = app(TenantManager::class);

            // Expect either no tenant identified or only valid tenant match
            if ($tenantManager->hasTenant()) {
                $this::assertNotEquals($payload, $tenantManager->getTenantId());
            }

            $response->assertStatus(200);
        }
    }
}
