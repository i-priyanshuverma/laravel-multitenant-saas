<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Mail\TeamInvitationMail;
use App\Models\TeamInvitation;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $owner;

    protected User $admin;

    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Acme Corporation',
            'slug' => 'acme',
            'status' => 'active',
        ]);

        app(TenantManager::class)->setTenant($this->tenant);

        $this->owner = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Alice Owner',
            'email' => 'alice@acme.com',
            'password' => 'secret123',
            'role' => UserRole::Owner,
        ]);

        $this->tenant->update(['owner_id' => $this->owner->id]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Bob Admin',
            'email' => 'bob@acme.com',
            'password' => 'secret123',
            'role' => UserRole::Admin,
        ]);

        $this->member = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Charlie Member',
            'email' => 'charlie@acme.com',
            'password' => 'secret123',
            'role' => UserRole::Member,
        ]);
    }

    public function test_owner_or_admin_can_update_member_role(): void
    {
        $response = $this->actingAs($this->admin)
            ->withHeader('X-Tenant', $this->tenant->slug)
            ->patch("/settings/team/{$this->member->id}", [
                'role' => 'admin',
            ]);

        $response->assertSessionHas('success');
        $this->assertEquals(UserRole::Admin, $this->member->fresh()->role);
    }

    public function test_standard_member_cannot_update_roles(): void
    {
        $response = $this->actingAs($this->member)
            ->withHeader('X-Tenant', $this->tenant->slug)
            ->patch("/settings/team/{$this->admin->id}", [
                'role' => 'member',
            ]);

        $response->assertForbidden();
    }

    public function test_cannot_modify_or_demote_workspace_owner(): void
    {
        $response = $this->actingAs($this->admin)
            ->withHeader('X-Tenant', $this->tenant->slug)
            ->patch("/settings/team/{$this->owner->id}", [
                'role' => 'member',
            ]);

        $response->assertForbidden();
        $this->assertEquals(UserRole::Owner, $this->owner->fresh()->role);
    }

    public function test_cannot_assign_owner_role_via_team_update(): void
    {
        $response = $this->actingAs($this->owner)
            ->withHeader('X-Tenant', $this->tenant->slug)
            ->patch("/settings/team/{$this->member->id}", [
                'role' => 'owner',
            ]);

        $response->assertSessionHasErrors(['role']);
    }

    public function test_owner_or_admin_can_revoke_pending_invitation(): void
    {
        $invitation = TeamInvitation::create([
            'tenant_id' => $this->tenant->id,
            'email' => 'newhire@acme.com',
            'role' => UserRole::Member,
            'token' => 'token-to-revoke-12345',
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($this->admin)
            ->withHeader('X-Tenant', $this->tenant->slug)
            ->delete("/team/invitations/{$invitation->id}");

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('team_invitations', [
            'id' => $invitation->id,
        ]);
    }

    public function test_standard_member_cannot_revoke_invitation(): void
    {
        $invitation = TeamInvitation::create([
            'tenant_id' => $this->tenant->id,
            'email' => 'newhire@acme.com',
            'role' => UserRole::Member,
            'token' => 'token-to-revoke-12345',
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($this->member)
            ->withHeader('X-Tenant', $this->tenant->slug)
            ->delete("/team/invitations/{$invitation->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('team_invitations', [
            'id' => $invitation->id,
        ]);
    }

    public function test_owner_or_admin_can_resend_invitation(): void
    {
        Mail::fake();

        $invitation = TeamInvitation::create([
            'tenant_id' => $this->tenant->id,
            'email' => 'resend@acme.com',
            'role' => UserRole::Member,
            'token' => 'old-token-12345',
            'expires_at' => now()->addDays(1),
        ]);

        $response = $this->actingAs($this->owner)
            ->withHeader('X-Tenant', $this->tenant->slug)
            ->post("/team/invitations/{$invitation->id}/resend");

        $response->assertSessionHas('success');
        $this->assertNotEquals('old-token-12345', $invitation->fresh()->token);

        Mail::assertQueued(TeamInvitationMail::class, function ($mail) {
            return $mail->hasTo('resend@acme.com');
        });
    }

    public function test_cross_tenant_cannot_revoke_another_tenants_invitation(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'Stark Industries',
            'slug' => 'stark',
            'status' => 'active',
        ]);

        $otherAdmin = User::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Tony Stark',
            'email' => 'tony@stark.com',
            'password' => 'secret123',
            'role' => UserRole::Admin,
        ]);

        $invitation = TeamInvitation::create([
            'tenant_id' => $this->tenant->id,
            'email' => 'candidate@acme.com',
            'role' => UserRole::Member,
            'token' => 'acme-invite-token',
            'expires_at' => now()->addDays(7),
        ]);

        app(TenantManager::class)->setTenant($otherTenant);

        $response = $this->actingAs($otherAdmin)
            ->withHeader('X-Tenant', $otherTenant->slug)
            ->delete("/team/invitations/{$invitation->id}");

        // Due to TenantScoped or Policy, cannot delete
        $this->assertTrue(in_array($response->status(), [403, 404], true));
        $this->assertDatabaseHas('team_invitations', [
            'id' => $invitation->id,
        ]);
    }
}
