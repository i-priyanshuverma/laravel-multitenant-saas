<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected TenantManager $tenantManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantManager = app(TenantManager::class);

        $this->tenantA = Tenant::create([
            'name' => 'Company A',
            'slug' => 'company-a',
            'status' => 'active',
        ]);

        $this->tenantB = Tenant::create([
            'name' => 'Company B',
            'slug' => 'company-b',
            'status' => 'active',
        ]);

        // Create user for Tenant A
        $this->tenantManager->setTenant($this->tenantA);
        User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'User A',
            'email' => 'usera@companya.com',
            'password' => 'password',
        ]);

        // Create user for Tenant B
        $this->tenantManager->setTenant($this->tenantB);
        User::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'User B',
            'email' => 'userb@companyb.com',
            'password' => 'password',
        ]);

        $this->tenantManager->forgetTenant();
    }

    public function test_tenant_a_cannot_view_tenant_b_data(): void
    {
        $this->tenantManager->setTenant($this->tenantA);

        $users = User::all();

        $this->assertCount(1, $users);
        $this->assertEquals('User A', $users->first()->name);
        $this->assertEquals($this->tenantA->id, $users->first()->tenant_id);
    }

    public function test_tenant_b_cannot_view_tenant_a_data(): void
    {
        $this->tenantManager->setTenant($this->tenantB);

        $users = User::all();

        $this->assertCount(1, $users);
        $this->assertEquals('User B', $users->first()->name);
        $this->assertEquals($this->tenantB->id, $users->first()->tenant_id);
    }

    public function test_automatic_tenant_id_assignment_on_model_creation(): void
    {
        $this->tenantManager->setTenant($this->tenantA);

        $user = User::create([
            'name' => 'User A2',
            'email' => 'usera2@companya.com',
            'password' => 'password',
        ]);

        $this->assertEquals($this->tenantA->id, $user->tenant_id);
    }
}
