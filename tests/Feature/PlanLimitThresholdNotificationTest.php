<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\PlanLimitThresholdNotification;
use App\Services\PlanLimitService;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PlanLimitThresholdNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Plan $plan;

    protected User $owner;

    protected User $admin;

    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = Plan::create([
            'name' => 'Growth',
            'slug' => 'growth',
            'stripe_price_id' => 'price_growth_test',
            'price_monthly' => 49.00,
            'price_yearly' => 490.00,
            'max_users' => 5,
            'max_storage_gb' => 20,
            'features' => ['priority_support'],
            'is_active' => true,
        ]);

        $this->tenant = Tenant::create([
            'name' => 'Alert Test Corp',
            'slug' => 'alertcorp',
            'plan_id' => $this->plan->id,
            'status' => 'active',
        ]);

        $this->owner = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Owner Alice',
            'email' => 'alice@alertcorp.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_super_admin' => false,
        ]);

        $this->tenant->update(['owner_id' => $this->owner->id]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Bob',
            'email' => 'bob@alertcorp.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_super_admin' => false,
        ]);

        $this->member = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Member Charlie',
            'email' => 'charlie@alertcorp.com',
            'password' => bcrypt('password'),
            'role' => 'member',
            'is_super_admin' => false,
        ]);

        app(TenantManager::class)->setTenant($this->tenant);
    }

    public function test_80_percent_threshold_triggers_notification_to_owner_and_admins(): void
    {
        Notification::fake();

        // 3 users existing (owner, admin, member). Add 4th user => 4 / 5 = 80%
        User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Member Dave',
            'email' => 'dave@alertcorp.com',
            'password' => bcrypt('password'),
            'role' => 'member',
        ]);

        $service = app(PlanLimitService::class);
        $threshold = $service->checkAndNotifyThreshold($this->tenant, 'users');

        $this->assertEquals(80, $threshold);

        // Sent to owner and admin
        Notification::assertSentTo(
            [$this->owner, $this->admin],
            PlanLimitThresholdNotification::class,
            function (PlanLimitThresholdNotification $notification) {
                return $notification->threshold === 80
                    && $notification->currentUsage === 4
                    && $notification->maxLimit === 5;
            }
        );

        // NOT sent to general members
        Notification::assertNotSentTo([$this->member], PlanLimitThresholdNotification::class);
    }

    public function test_100_percent_threshold_triggers_critical_alert(): void
    {
        Notification::fake();

        // Fill to 5 users = 100%
        User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'User 4',
            'email' => 'user4@alertcorp.com',
            'password' => bcrypt('password'),
            'role' => 'member',
        ]);
        User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'User 5',
            'email' => 'user5@alertcorp.com',
            'password' => bcrypt('password'),
            'role' => 'member',
        ]);

        $service = app(PlanLimitService::class);
        $threshold = $service->checkAndNotifyThreshold($this->tenant, 'users');

        $this->assertEquals(100, $threshold);

        Notification::assertSentTo(
            $this->owner,
            PlanLimitThresholdNotification::class,
            fn ($notification) => $notification->threshold === 100 && $notification->currentUsage === 5
        );
    }

    public function test_notifications_are_throttled_by_cache(): void
    {
        Notification::fake();

        // Add 4th user = 80%
        User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'User 4',
            'email' => 'user4@alertcorp.com',
            'password' => bcrypt('password'),
            'role' => 'member',
        ]);

        $service = app(PlanLimitService::class);

        // First call sends notification
        $first = $service->checkAndNotifyThreshold($this->tenant, 'users');
        $this->assertEquals(80, $first);
        Notification::assertSentTimes(PlanLimitThresholdNotification::class, 2); // Owner + Admin

        // Second call is throttled
        $second = $service->checkAndNotifyThreshold($this->tenant, 'users');
        $this->assertNull($second);
        Notification::assertSentTimes(PlanLimitThresholdNotification::class, 2);

        // Forced call bypasses throttle
        $third = $service->checkAndNotifyThreshold($this->tenant, 'users', force: true);
        $this->assertEquals(80, $third);
        Notification::assertSentTimes(PlanLimitThresholdNotification::class, 4);
    }

    public function test_unlimited_plan_does_not_trigger_notification(): void
    {
        Notification::fake();

        $unlimitedPlan = Plan::create([
            'name' => 'Enterprise',
            'slug' => 'enterprise-test',
            'stripe_price_id' => 'price_ent_test',
            'price_monthly' => 299.00,
            'price_yearly' => 2990.00,
            'max_users' => 0,
            'max_storage_gb' => 0,
            'features' => ['all'],
            'is_active' => true,
        ]);

        $this->tenant->update(['plan_id' => $unlimitedPlan->id]);
        $this->tenant->refresh();

        $service = app(PlanLimitService::class);
        $threshold = $service->checkAndNotifyThreshold($this->tenant, 'users');

        $this->assertNull($threshold);
        Notification::assertNothingSent();
    }

    public function test_reset_threshold_cache_clears_throttle(): void
    {
        $service = app(PlanLimitService::class);

        Cache::put("tenant:{$this->tenant->id}:plan_limit_alert:users:80", true, 3600);
        Cache::put("tenant:{$this->tenant->id}:plan_limit_alert:users:100", true, 3600);

        $this->assertTrue(Cache::has("tenant:{$this->tenant->id}:plan_limit_alert:users:80"));

        $service->resetThresholdCache($this->tenant, 'users');

        $this->assertFalse(Cache::has("tenant:{$this->tenant->id}:plan_limit_alert:users:80"));
        $this->assertFalse(Cache::has("tenant:{$this->tenant->id}:plan_limit_alert:users:100"));
    }

    public function test_notification_mail_content(): void
    {
        $notification = new PlanLimitThresholdNotification(
            tenant: $this->tenant,
            resource: 'users',
            currentUsage: 4,
            maxLimit: 5,
            threshold: 80,
        );

        $mail = $notification->toMail($this->owner);

        $this->assertStringContainsString('80%', $mail->subject);
        $this->assertStringContainsString('Upgrade Plan', $mail->actionText);
        $this->assertStringContainsString('/billing', $mail->actionUrl);
    }

    public function test_artisan_command_scans_and_alerts_tenants(): void
    {
        Notification::fake();

        // Bring to 80%
        User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'User 4',
            'email' => 'user4@alertcorp.com',
            'password' => bcrypt('password'),
            'role' => 'member',
        ]);

        $this->artisan('tenants:check-plan-limits', [
            '--tenant' => $this->tenant->slug,
            '--force' => true,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('Evaluating plan limits for 1 tenant(s)...')
            ->expectsOutputToContain('reached 80% threshold for users.');

        Notification::assertSentTo($this->owner, PlanLimitThresholdNotification::class);
    }
}
