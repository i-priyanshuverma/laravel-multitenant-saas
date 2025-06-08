<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_super_admin_cannot_access_admin_panel(): void
    {
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'regular@example.com',
            'password' => 'password',
            'is_super_admin' => false,
        ]);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(403);
    }

    public function test_super_admin_can_access_admin_panel(): void
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@platform.com',
            'password' => 'password',
            'is_super_admin' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200);
    }

    public function test_super_admin_can_impersonate_tenant(): void
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@platform.com',
            'password' => 'password',
            'is_super_admin' => true,
        ]);

        $tenant = Tenant::create([
            'name' => 'Oscorp Industries',
            'slug' => 'oscorp',
            'status' => 'active',
        ]);

        $owner = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Norman Osborn',
            'email' => 'norman@oscorp.com',
            'password' => 'password',
        ]);

        $tenant->update(['owner_id' => $owner->id]);

        $response = $this->actingAs($admin)->get('/admin/impersonate/' . $tenant->id);

        $response->assertRedirect('/dashboard');
        $this->assertEquals($owner->id, auth()->id());
        $this->assertEquals($admin->id, session('impersonator_id'));
    }
}
