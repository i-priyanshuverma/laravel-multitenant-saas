<?php

namespace Tests\Feature;

use App\Models\TeamInvitation;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_team_invitation(): void
    {
        $tenant = Tenant::create([
            'name' => 'Cyberdyne Systems',
            'slug' => 'cyberdyne',
            'status' => 'active',
        ]);

        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($tenant);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Sarah Connor',
            'email' => 'sarah@cyberdyne.com',
            'password' => 'secret123',
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)
            ->withHeader('X-Tenant', $tenant->slug)
            ->post('/team/invitations', [
                'email' => 'john@cyberdyne.com',
                'role' => 'member',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('team_invitations', [
            'tenant_id' => $tenant->id,
            'email' => 'john@cyberdyne.com',
            'role' => 'member',
        ]);
    }
}
