<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use App\Notifications\PlanLimitThresholdNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class PlanLimitService
{
    /**
     * Check if the tenant has reached the maximum user count for their plan.
     */
    public function hasReachedUserLimit(Tenant $tenant): bool
    {
        $plan = $tenant->plan;

        if (! $plan || $plan->max_users === 0) {
            return false;
        }

        $currentUserCount = $tenant->users()->count();

        return $currentUserCount >= $plan->max_users;
    }

    /**
     * Get the number of remaining user seats for the tenant.
     */
    public function remainingUserSeats(Tenant $tenant): ?int
    {
        $plan = $tenant->plan;

        if (! $plan || $plan->max_users === 0) {
            return null; // unlimited
        }

        $currentUserCount = $tenant->users()->count();

        return (int) max(0, $plan->max_users - $currentUserCount);
    }

    /**
     * Get user seat utilization percentage.
     */
    public function getUserUsagePercentage(Tenant $tenant): ?float
    {
        $plan = $tenant->plan;

        if (! $plan || $plan->max_users === 0) {
            return null;
        }

        return round(($tenant->users()->count() / $plan->max_users) * 100, 1);
    }

    /**
     * Check if the tenant has exceeded the storage allocation for their plan.
     */
    public function hasExceededStorageLimit(Tenant $tenant, int $currentStorageMb = 0): bool
    {
        $plan = $tenant->plan;

        if (! $plan || $plan->max_storage_gb === 0) {
            return false;
        }

        $maxStorageMb = $plan->max_storage_gb * 1024;

        return $currentStorageMb >= $maxStorageMb;
    }

    /**
     * Get storage utilization percentage.
     */
    public function getStorageUsagePercentage(Tenant $tenant, int $currentStorageMb = 0): ?float
    {
        $plan = $tenant->plan;

        if (! $plan || $plan->max_storage_gb === 0) {
            return null;
        }

        $maxStorageMb = $plan->max_storage_gb * 1024;

        return round(($currentStorageMb / $maxStorageMb) * 100, 1);
    }

    /**
     * Get recipients who should receive plan limit notifications (tenant owner and admins).
     *
     * @return Collection<int, User>
     */
    public function getNotificationRecipients(Tenant $tenant): Collection
    {
        /** @var Collection<int, User> $recipients */
        $recipients = collect();

        $owner = $tenant->owner;
        if ($owner instanceof User) {
            $recipients->push($owner);
        }

        /** @var Collection<int, User> $admins */
        $admins = $tenant->users()->where('role', 'admin')->get();
        foreach ($admins as $admin) {
            if (! $recipients->contains('id', $admin->id)) {
                $recipients->push($admin);
            }
        }

        return $recipients;
    }

    /**
     * Check if a tenant has reached or exceeded an alert threshold (80% or 100%)
     * and notify tenant admins if an alert has not yet been sent within the throttle window.
     *
     * @return int|null The threshold alerted (80 or 100), or null if no alert was triggered.
     */
    public function checkAndNotifyThreshold(
        Tenant $tenant,
        string $resource = 'users',
        int $currentStorageMb = 0,
        bool $force = false
    ): ?int {
        $plan = $tenant->plan;

        if (! $plan) {
            return null;
        }

        $current = 0;
        $max = 0;

        if ($resource === 'users') {
            if ($plan->max_users === 0) {
                return null;
            }
            $current = $tenant->users()->count();
            $max = $plan->max_users;
        } elseif ($resource === 'storage') {
            if ($plan->max_storage_gb === 0) {
                return null;
            }
            $current = (int) ceil($currentStorageMb / 1024);
            $max = $plan->max_storage_gb;
        } else {
            return null;
        }

        $percentage = ($current / $max) * 100;

        $threshold = null;
        if ($percentage >= 100) {
            $threshold = 100;
        } elseif ($percentage >= 80) {
            $threshold = 80;
        }

        if ($threshold === null) {
            return null;
        }

        $cacheKey = "tenant:{$tenant->id}:plan_limit_alert:{$resource}:{$threshold}";

        if (! $force && Cache::has($cacheKey)) {
            return null;
        }

        $recipients = $this->getNotificationRecipients($tenant);

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new PlanLimitThresholdNotification(
                tenant: $tenant,
                resource: $resource,
                currentUsage: $current,
                maxLimit: $max,
                threshold: $threshold,
            ));
        }

        Cache::put($cacheKey, true, now()->addDay());

        return $threshold;
    }

    /**
     * Reset the notification throttle cache when usage drops below thresholds.
     */
    public function resetThresholdCache(Tenant $tenant, string $resource = 'users'): void
    {
        Cache::forget("tenant:{$tenant->id}:plan_limit_alert:{$resource}:80");
        Cache::forget("tenant:{$tenant->id}:plan_limit_alert:{$resource}:100");
    }

    /**
     * Get a summary of the tenant's current plan usage.
     *
     * @return array{users_current: int, users_max: int|null, users_remaining: int|null, storage_max_gb: int|null, plan_name: string|null}
     */
    public function getUsageSummary(Tenant $tenant): array
    {
        $plan = $tenant->plan;
        $currentUsers = $tenant->users()->count();

        return [
            'users_current' => $currentUsers,
            'users_max' => $plan?->max_users,
            'users_remaining' => $this->remainingUserSeats($tenant),
            'storage_max_gb' => $plan?->max_storage_gb,
            'plan_name' => $plan?->name,
        ];
    }
}
