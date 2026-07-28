<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonateController extends Controller
{
    /**
     * Impersonate tenant owner.
     */
    public function impersonate(Request $request, Tenant $tenant): RedirectResponse
    {
        /** @var User|null $currentUser */
        $currentUser = Auth::user();

        if (! $currentUser || ! $currentUser->isSuperAdmin()) {
            abort(403, 'Only super-admins can impersonate tenants.');
        }

        /** @var User|null $owner */
        $owner = $tenant->owner ?: User::where('tenant_id', $tenant->id)->first();

        if (! $owner) {
            return back()->with('error', 'Tenant has no assigned user or owner to impersonate.');
        }

        // Store impersonator in session
        $request->session()->put('impersonator_id', $currentUser->id);

        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($tenant);

        Auth::login($owner);

        return redirect('/dashboard')->with('success', 'Now impersonating workspace: '.$tenant->name);
    }

    /**
     * Stop impersonating and return to super-admin panel.
     */
    public function leave(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->get('impersonator_id');

        if (! $impersonatorId) {
            return redirect('/');
        }

        /** @var User $superAdmin */
        $superAdmin = User::where('id', $impersonatorId)->firstOrFail();
        $request->session()->forget('impersonator_id');

        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);
        $tenantManager->forgetTenant();

        Auth::login($superAdmin);

        return redirect('/admin')->with('success', 'Exited impersonation mode. Welcome back to Admin Panel.');
    }
}
