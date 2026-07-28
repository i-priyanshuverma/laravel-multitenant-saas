<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_render_register_page(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_can_register_new_tenant_and_owner(): void
    {
        $response = $this->post('/register', [
            'company_name' => 'Acme Corp',
            'company_slug' => 'acme',
            'name' => 'John Doe',
            'email' => 'john@acme.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertDatabaseHas('tenants', [
            'name' => 'Acme Corp',
            'slug' => 'acme',
        ]);

        $tenant = Tenant::where('slug', 'acme')->first();
        $this->assertNotNull($tenant);

        $this->assertDatabaseHas('domains', [
            'tenant_id' => $tenant->id,
            'is_primary' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'tenant_id' => $tenant->id,
            'email' => 'john@acme.com',
            'role' => 'owner',
        ]);

        $this->assertAuthenticated();
    }
}
