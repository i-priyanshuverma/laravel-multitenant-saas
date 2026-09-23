<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PlanLimitService;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanUsageLimitsTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Plan $plan;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = Plan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'stripe_price_id' => 'price_starter_test',
            'price_monthly' => 29.00,
            'price_yearly' => 290.00,
            'max_users' => 3,
            'max_storage_gb' => 10,
            'features' => ['basic_support'],
            'is_active' => true,
        ]);

        $this->tenant = Tenant::create([
            'name' => 'Limit Test Corp',
            'slug' => 'limitcorp',
            'plan_id' => $this->plan->id,
            'status' => 'active',
        ]);

        $this->owner = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Owner',
            'email' => 'owner@limitcorp.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_super_admin' => false,
        ]);

        $this->tenant->update(['owner_id' => $this->owner->id]);

        // Set tenant context
        app(TenantManager::class)->setTenant($this->tenant);
    }

    public function test_service_detects_user_limit_not_reached(): void
    {
        $service = app(PlanLimitService::class);

        $this->assertFalse($service->hasReachedUserLimit($this->tenant));
        $this->assertEquals(2, $service->remainingUserSeats($this->tenant)); // 3 max - 1 owner = 2 remaining
    }

    public function test_service_detects_user_limit_reached(): void
    {
        // Add users to fill the 3-seat limit
        User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'User 2',
            'email' => 'user2@limitcorp.com',
            'password' => bcrypt('password'),
            'role' => 'member',
        ]);

        User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'User 3',
            'email' => 'user3@limitcorp.com',
            'password' => bcrypt('password'),
            'role' => 'member',
        ]);

        $service = app(PlanLimitService::class);

        $this->assertTrue($service->hasReachedUserLimit($this->tenant));
        $this->assertEquals(0, $service->remainingUserSeats($this->tenant));
    }

    public function test_service_returns_null_for_unlimited_plan(): void
    {
        $unlimitedPlan = Plan::create([
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'stripe_price_id' => 'price_enterprise_test',
            'price_monthly' => 299.00,
            'price_yearly' => 2990.00,
            'max_users' => 0,
            'max_storage_gb' => 0,
            'features' => ['priority_support', 'sso'],
            'is_active' => true,
        ]);

        $this->tenant->update(['plan_id' => $unlimitedPlan->id]);
        $this->tenant->refresh();

        $service = app(PlanLimitService::class);

        $this->assertFalse($service->hasReachedUserLimit($this->tenant));
        $this->assertNull($service->remainingUserSeats($this->tenant));
    }

    public function test_service_detects_storage_limit_exceeded(): void
    {
        $service = app(PlanLimitService::class);

        // 10 GB plan = 10240 MB
        $this->assertFalse($service->hasExceededStorageLimit($this->tenant, 5000));
        $this->assertTrue($service->hasExceededStorageLimit($this->tenant, 10240));
        $this->assertTrue($service->hasExceededStorageLimit($this->tenant, 15000));
    }

    public function test_service_returns_usage_summary(): void
    {
        $service = app(PlanLimitService::class);

        $summary = $service->getUsageSummary($this->tenant);

        $this->assertEquals(1, $summary['users_current']);
        $this->assertEquals(3, $summary['users_max']);
        $this->assertEquals(2, $summary['users_remaining']);
        $this->assertEquals(10, $summary['storage_max_gb']);
        $this->assertEquals('Starter', $summary['plan_name']);
    }

    public function test_middleware_allows_invitation_when_under_limit(): void
    {
        $response = $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant' => $this->tenant->slug])
            ->post(route('team.invitations.store'), [
                'email' => 'newuser@limitcorp.com',
                'role' => 'member',
            ]);

        $response->assertSessionHas('success');
    }

    public function test_middleware_blocks_invitation_when_limit_reached(): void
    {
        // Fill up to the 3-user limit
        User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'User 2',
            'email' => 'user2@limitcorp.com',
            'password' => bcrypt('password'),
            'role' => 'member',
        ]);

        User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'User 3',
            'email' => 'user3@limitcorp.com',
            'password' => bcrypt('password'),
            'role' => 'member',
        ]);

        $response = $this->actingAs($this->owner)
            ->withHeaders(['X-Tenant' => $this->tenant->slug])
            ->post(route('team.invitations.store'), [
                'email' => 'blocked@limitcorp.com',
                'role' => 'member',
            ]);

        $response->assertSessionHas('error');
        $this->assertStringContains('User limit reached', session('error'));
    }

    /**
     * Custom assertion for string containment.
     */
    private function assertStringContains(string $needle, ?string $haystack): void
    {
        $this->assertNotNull($haystack);
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' contains '{$needle}'."
        );
    }
}
