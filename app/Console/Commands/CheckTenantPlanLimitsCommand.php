<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\PlanLimitService;
use Illuminate\Console\Command;

class CheckTenantPlanLimitsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:check-plan-limits
                            {--tenant= : Check a specific tenant by slug}
                            {--force : Bypass the 24-hour throttle cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan active tenants, evaluate plan resource utilization, and notify admins of reached thresholds (80%/100%).';

    /**
     * Execute the console command.
     */
    public function handle(PlanLimitService $planLimitService): int
    {
        $tenantSlug = $this->option('tenant');
        $force = (bool) $this->option('force');

        $query = Tenant::with(['plan', 'owner', 'users'])->where('status', 'active');

        if (is_string($tenantSlug) && $tenantSlug !== '') {
            $query->where('slug', $tenantSlug);
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->info('No active tenants found to evaluate.');

            return self::SUCCESS;
        }

        $this->info("Evaluating plan limits for {$tenants->count()} tenant(s)...");

        $rows = [];

        foreach ($tenants as $tenant) {
            $plan = $tenant->plan;
            $planName = $plan ? $plan->name : 'None';

            $userPercentage = $planLimitService->getUserUsagePercentage($tenant);
            $userRatio = $plan && $plan->max_users > 0
                ? "{$tenant->users->count()}/{$plan->max_users} (".($userPercentage ?? 0).'%)'
                : "{$tenant->users->count()} (Unlimited)";

            $userAlert = $planLimitService->checkAndNotifyThreshold(
                tenant: $tenant,
                resource: 'users',
                force: $force,
            );

            if ($userAlert) {
                $this->warn("Alert: Tenant [{$tenant->name}] reached {$userAlert}% threshold for users.");
            }

            $alertStatus = $userAlert ? "{$userAlert}% Alert Sent" : 'OK / None';

            $rows[] = [
                $tenant->name,
                $tenant->slug,
                $planName,
                $userRatio,
                $alertStatus,
            ];
        }

        $this->table(
            ['Tenant Name', 'Slug', 'Plan', 'Users Allocation', 'Status'],
            $rows,
        );

        return self::SUCCESS;
    }
}
