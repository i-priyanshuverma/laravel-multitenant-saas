<?php

namespace App\Http\Middleware;

use App\Models\Plan;
use App\Services\PlanLimitService;
use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforcePlanLimits
{
    public function __construct(
        protected TenantManager $tenantManager,
        protected PlanLimitService $planLimitService,
    ) {}

    /**
     * Block requests that would exceed the tenant's plan limits.
     *
     * Usage in routes: ->middleware('plan.limits:users') or ->middleware('plan.limits:storage')
     */
    public function handle(Request $request, Closure $next, string $resource = 'users'): Response
    {
        $tenant = $this->tenantManager->getTenant();

        if (! $tenant) {
            return $next($request);
        }

        if ($resource === 'users' && $this->planLimitService->hasReachedUserLimit($tenant)) {
            $plan = $tenant->plan;

            if (! $plan instanceof Plan) {
                return $next($request);
            }

            $limit = $plan->max_users;

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "User limit reached. Your {$plan->name} plan allows a maximum of {$limit} users.",
                    'error' => 'plan_limit_exceeded',
                    'upgrade_required' => true,
                ], 403);
            }

            return back()->with('error', "User limit reached. Your {$plan->name} plan allows a maximum of {$limit} users. Please upgrade your plan.");
        }

        return $next($request);
    }
}
