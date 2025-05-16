<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_password_reset_link_with_tenant_context(): void
    {
        $tenant = Tenant::create([
            'name' => 'Apex Logistics',
            'slug' => 'apex',
            'status' => 'active',
        ]);

        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($tenant);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Alice Tech',
            'email' => 'alice@apex.com',
            'password' => 'secret123',
        ]);

        $response = $this->withHeader('X-Tenant', $tenant->slug)
            ->post('/forgot-password', [
                'email' => 'alice@apex.com',
            ]);

        $response->assertSessionHas('status');
    }
}
