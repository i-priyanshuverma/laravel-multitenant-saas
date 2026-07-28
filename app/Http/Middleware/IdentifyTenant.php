<?php

namespace App\Http\Middleware;

use App\Models\Domain;
use App\Models\Tenant;
use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function __construct(protected TenantManager $tenantManager) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenant($request);

        if ($tenant) {
            $this->tenantManager->setTenant($tenant);
            $request->attributes->set('tenant', $tenant);
        }

        return $next($request);
    }

    /**
     * Resolve tenant by header, full domain name, or subdomain slug.
     */
    protected function resolveTenant(Request $request): ?Tenant
    {
        // 1. Check Header (X-Tenant or X-Tenant-ID)
        $tenantHeader = $request->header('X-Tenant') ?: $request->header('X-Tenant-ID');
        if ($tenantHeader && is_string($tenantHeader)) {
            $tenant = Tenant::where('id', $tenantHeader)->orWhere('slug', $tenantHeader)->first();
            if ($tenant instanceof Tenant) {
                return $tenant;
            }
        }

        // 2. Check Host / Domain match
        $host = $request->getHost();

        // Direct domain match
        /** @var Domain|null $domainRecord */
        $domainRecord = Domain::where('domain', $host)->first();
        if ($domainRecord && $domainRecord->tenant instanceof Tenant) {
            return $domainRecord->tenant;
        }

        // Subdomain extraction (e.g. acme.domain.com or acme.localhost)
        $parts = explode('.', $host);
        if (count($parts) >= 2) {
            $subdomain = $parts[0];
            if ($subdomain !== 'www' && $subdomain !== 'admin' && $subdomain !== 'api') {
                /** @var Tenant|null $tenant */
                $tenant = Tenant::where('slug', $subdomain)->first();
                if ($tenant instanceof Tenant) {
                    return $tenant;
                }
            }
        }

        return null;
    }
}
