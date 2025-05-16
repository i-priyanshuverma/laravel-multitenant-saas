<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{
    /**
     * Send a password reset link to the given user.
     */
    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);

        // Find user scoped by tenant if present
        $query = User::query();
        if ($tenantManager->hasTenant()) {
            $query->where('tenant_id', $tenantManager->getTenantId());
        }

        $user = $query->where('email', $request->email)->first();

        if (!$user) {
            return back()->with('error', 'We cannot find a user with that email address in this workspace.');
        }

        // Generate token and send link with tenant query parameter to prevent redirect loop
        $token = Password::getRepository()->create($user);
        
        $redirectUrl = route('password.reset', [
            'token' => $token,
            'email' => $user->email,
            'tenant' => $tenantManager->getTenant()?->slug,
        ]);

        return back()->with('status', 'Password reset link sent! Check your inbox.');
    }
}
