<?php

namespace App\Models\Traits;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Services\TenantManager;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait TenantScoped
{
    /**
     * Boot the trait to attach global tenant scope and auto-assign tenant_id.
     */
    protected static function bootTenantScoped(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            /** @var TenantManager $tenantManager */
            $tenantManager = app(TenantManager::class);

            if ($tenantManager->hasTenant() && empty($model->tenant_id)) {
                $model->tenant_id = $tenantManager->getTenantId();
            }
        });
    }

    /**
     * Get the tenant that owns the model.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
