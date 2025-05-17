<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    /**
     * Display team management roster and invitations.
     */
    public function index(Request $request): Response
    {
        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);

        $members = User::where('tenant_id', $tenantManager->getTenantId())->get();
        $invitations = TeamInvitation::where('tenant_id', $tenantManager->getTenantId())->get();

        return Inertia::render('Settings/Team', [
            'members' => $members,
            'invitations' => $invitations,
        ]);
    }

    /**
     * Remove a member from the team.
     */
    public function destroy(User $user): RedirectResponse
    {
        /** @var TenantManager $tenantManager */
        $tenantManager = app(TenantManager::class);

        if ($user->tenant_id !== $tenantManager->getTenantId()) {
            abort(403);
        }

        if ($user->role === 'owner') {
            return back()->with('error', 'Cannot remove workspace owner.');
        }

        $user->delete();

        return back()->with('success', 'Team member removed successfully.');
    }
}
