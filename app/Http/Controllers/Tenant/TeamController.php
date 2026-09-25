<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateTeamMemberRoleRequest;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\PlanLimitService;
use App\Services\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function __construct(
        protected TenantManager $tenantManager,
        protected PlanLimitService $planLimitService,
    ) {}

    /**
     * Display team management roster and invitations.
     */
    public function index(Request $request): Response
    {
        $tenant = $this->tenantManager->getTenant();

        $members = $tenant ? $tenant->users : collect();
        $invitations = $tenant ? TeamInvitation::where('tenant_id', $tenant->id)->get() : collect();

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
        Gate::authorize('delete', $user);

        $user->delete();

        $tenant = $this->tenantManager->getTenant();
        if ($tenant) {
            $this->planLimitService->resetThresholdCache($tenant, 'users');
        }

        return back()->with('success', 'Team member removed successfully.');
    }

    /**
     * Update a team member's role.
     */
    public function updateRole(UpdateTeamMemberRoleRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $user->update([
            'role' => $request->validated('role'),
        ]);

        return back()->with('success', 'Team member role updated successfully.');
    }
}
