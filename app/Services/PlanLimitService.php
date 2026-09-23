<?php

namespace App\Services;

use App\Models\Tenant;

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
