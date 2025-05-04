<?php

namespace App\Services;

use App\Models\Tenant;

class TenantManager
{
    protected ?Tenant $tenant = null;

    /**
     * Set the current tenant for the request.
     */
    public function setTenant(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    /**
     * Get the current active tenant.
     */
    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }

    /**
     * Check if a tenant is currently resolved.
     */
    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }

    /**
     * Get current tenant ID or null.
     */
    public function getTenantId(): ?string
    {
        return $this->tenant?->id;
    }

    /**
     * Clear the active tenant.
     */
    public function forgetTenant(): void
    {
        $this->tenant = null;
    }
}
