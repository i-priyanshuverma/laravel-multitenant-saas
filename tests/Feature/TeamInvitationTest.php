<?php

namespace Tests\Feature;

use App\Mail\TeamInvitationMail;
use App\Models\TeamInvitation;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TeamInvitationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Cyberdyne Systems',
            'slug' => 'cyberdyne',
            'status' => 'active',
        ]);

        app(TenantManager::class)->setTenant($this->tenant);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Sarah Connor',
            'email' => 'sarah@cyberdyne.com',
            'password' => 'secret123',
            'role' => 'admin',
        ]);
    }

    public function test_authenticated_user_can_create_team_invitation(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->user)
            ->withHeader('X-Tenant', $this->tenant->slug)
            ->post('/team/invitations', [
                'email' => 'john@cyberdyne.com',
                'role' => 'member',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('team_invitations', [
            'tenant_id' => $this->tenant->id,
            'email' => 'john@cyberdyne.com',
            'role' => 'member',
        ]);

        Mail::assertQueued(TeamInvitationMail::class, function ($mail) {
            return $mail->hasTo('john@cyberdyne.com');
        });
    }

    public function test_cannot_invite_existing_workspace_member(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->user)
            ->withHeader('X-Tenant', $this->tenant->slug)
            ->post('/team/invitations', [
                'email' => 'sarah@cyberdyne.com', // Already a member
                'role' => 'member',
            ]);

        $response->assertSessionHas('error');
        Mail::assertNothingQueued();
    }

    public function test_accepting_invitation_creates_user_logs_in_and_redirects(): void
    {
        $invitation = TeamInvitation::create([
            'tenant_id' => $this->tenant->id,
            'email' => 'kyle@cyberdyne.com',
            'role' => 'member',
            'token' => 'valid-invitation-token-12345',
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->get('/invitations/'.$invitation->token.'/accept');

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'tenant_id' => $this->tenant->id,
            'email' => 'kyle@cyberdyne.com',
            'role' => 'member',
        ]);

        $this->assertDatabaseMissing('team_invitations', [
            'id' => $invitation->id,
        ]);

        $this->assertTrue(Auth::check());
        $this->assertEquals('kyle@cyberdyne.com', Auth::user()?->email);
    }
}
