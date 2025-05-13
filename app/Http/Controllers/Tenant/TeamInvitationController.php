<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TeamInvitationController extends Controller
{
    /**
     * Store a new team invitation.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', 'string', 'in:admin,member,viewer'],
        ]);

        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);

        if (!$tenantManager->hasTenant()) {
            return back()->with('error', 'Active tenant context required.');
        }

        $invitation = TeamInvitation::create([
            'tenant_id' => $tenantManager->getTenantId(),
            'email' => strtolower($request->email),
            'role' => $request->role,
            'token' => Str::random(32),
            'expires_at' => now()->addDays(7),
        ]);

        return back()->with('success', 'Invitation created successfully for ' . $invitation->email);
    }

    /**
     * Accept a team invitation.
     */
    public function accept(string $token): RedirectResponse
    {
        $invitation = TeamInvitation::where('token', $token)->firstOrFail();

        if ($invitation->isExpired()) {
            return redirect('/login')->with('error', 'Invitation token has expired.');
        }

        // Set tenant context
        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($invitation->tenant);

        // Check if user already exists
        $user = User::where('email', $invitation->email)->first();

        if (!$user) {
            $user = User::create([
                'tenant_id' => $invitation->tenant_id,
                'name' => explode('@', $invitation->email)[0],
                'email' => $invitation->email,
                'password' => Hash::make(Str::random(16)),
                'role' => $invitation->role,
            ]);
        } else {
            $user->update([
                'tenant_id' => $invitation->tenant_id,
                'role' => $invitation->role,
            ]);
        }

        $invitation->delete();

        return redirect('/login')->with('success', 'Invitation accepted! Please sign in to your new workspace.');
    }
}
