<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Apex Logistics',
            'slug' => 'apex',
            'status' => 'active',
        ]);

        app(TenantManager::class)->setTenant($this->tenant);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Alice Tech',
            'email' => 'alice@apex.com',
            'password' => bcrypt('oldpassword123'),
        ]);
    }

    public function test_sends_password_reset_link_with_tenant_context(): void
    {
        Notification::fake();

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->post('/forgot-password', [
                'email' => 'alice@apex.com',
            ]);

        $response->assertSessionHas('status');
        Notification::assertSentTo($this->user, ResetPasswordNotification::class);
    }

    public function test_does_not_reveal_non_existent_user_on_reset_request(): void
    {
        Notification::fake();

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->post('/forgot-password', [
                'email' => 'nonexistent@apex.com',
            ]);

        // Generic status message returned, no email sent
        $response->assertSessionHas('status');
        Notification::assertNothingSent();
    }

    public function test_can_reset_password_with_valid_token(): void
    {
        $token = Password::broker()->createToken($this->user);

        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->post('/reset-password', [
                'token' => $token,
                'email' => 'alice@apex.com',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        $this->user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->user->password));
    }

    public function test_cannot_reset_password_with_invalid_token(): void
    {
        $response = $this->withHeader('X-Tenant', $this->tenant->slug)
            ->from('/reset-password/invalid-token')
            ->post('/reset-password', [
                'token' => 'invalid-token',
                'email' => 'alice@apex.com',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertSessionHasErrors('email');
        $this->user->refresh();
        $this->assertTrue(Hash::check('oldpassword123', $this->user->password));
    }
}
