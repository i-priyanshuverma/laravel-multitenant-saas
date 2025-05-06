<?php

namespace App\Http\Middleware;

use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EnsureTenantIsSet
{
    public function __construct(protected TenantManager $tenantManager)
    {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->tenantManager->hasTenant()) {
            throw new NotFoundHttpException('Tenant not found or invalid tenant context.');
        }

        return $next($request);
    }
}
