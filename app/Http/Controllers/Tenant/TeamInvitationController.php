<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreInvitationRequest;
use App\Mail\TeamInvitationMail;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\PlanLimitService;
use App\Services\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TeamInvitationController extends Controller
{
    public function __construct(
        protected TenantManager $tenantManager,
        protected PlanLimitService $planLimitService,
    ) {}

    /**
     * Store a new team invitation.
     */
    public function store(StoreInvitationRequest $request): RedirectResponse
    {
        Gate::authorize('create', TeamInvitation::class);

        $tenant = $this->tenantManager->getTenant();

        if (! $tenant) {
            return back()->with('error', 'Active tenant context required.');
        }

        $email = strtolower($request->email);

        // Prevent inviting existing tenant members
        if ($tenant->users()->where('email', $email)->exists()) {
            return back()->with('error', 'This user is already a member of this workspace.');
        }

        // Reuse or refresh pending invitation if one already exists
        $invitation = TeamInvitation::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'email' => $email,
            ],
            [
                'role' => $request->role,
                'token' => Str::random(32),
                'expires_at' => now()->addDays(7),
            ]
        );

        Log::info('Team invitation created', [
            'tenant_id' => $tenant->id,
            'email' => $invitation->email,
            'role' => $invitation->role,
        ]);

        try {
            Mail::to($invitation->email)->queue(new TeamInvitationMail($invitation));
        } catch (\Throwable $e) {
            Log::warning('Failed to queue invitation email: '.$e->getMessage(), [
                'invitation_id' => $invitation->id,
            ]);
        }

        return back()->with('success', 'Invitation created successfully for '.$invitation->email);
    }

    /**
     * Accept a team invitation.
     */
    public function accept(string $token): RedirectResponse
    {
        /** @var TeamInvitation $invitation */
        $invitation = TeamInvitation::where('token', $token)->firstOrFail();

        if ($invitation->isExpired()) {
            return redirect('/login')->with('error', 'Invitation token has expired.');
        }

        $tenant = $invitation->tenant;

        if ($tenant) {
            $this->tenantManager->setTenant($tenant);
        }

        $user = DB::transaction(function () use ($invitation, $tenant) {
            // Check if user already exists
            $user = User::where('email', $invitation->email)->first();

            if (! $user) {
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

            if ($tenant) {
                $this->planLimitService->checkAndNotifyThreshold($tenant, 'users');
            }

            return $user;
        });

        Log::info('Team invitation accepted', [
            'tenant_id' => $invitation->tenant_id,
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        Auth::login($user);

        $tenantName = $tenant ? $tenant->name : 'the workspace';

        return redirect('/dashboard')->with('success', "Welcome to {$tenantName}! Your invitation has been accepted.");
    }

    /**
     * Revoke / cancel a pending team invitation.
     */
    public function destroy(TeamInvitation $invitation): RedirectResponse
    {
        Gate::authorize('delete', $invitation);

        $email = $invitation->email;
        $invitation->delete();

        Log::info('Team invitation revoked', [
            'tenant_id' => $invitation->tenant_id,
            'email' => $email,
        ]);

        return back()->with('success', "Invitation for {$email} has been revoked.");
    }

    /**
     * Resend a pending team invitation.
     */
    public function resend(TeamInvitation $invitation): RedirectResponse
    {
        Gate::authorize('resend', $invitation);

        $invitation->update([
            'token' => Str::random(32),
            'expires_at' => now()->addDays(7),
        ]);

        try {
            Mail::to($invitation->email)->queue(new TeamInvitationMail($invitation));
        } catch (\Throwable $e) {
            Log::warning('Failed to queue resent invitation email: '.$e->getMessage(), [
                'invitation_id' => $invitation->id,
            ]);
        }

        Log::info('Team invitation resent', [
            'tenant_id' => $invitation->tenant_id,
            'email' => $invitation->email,
        ]);

        return back()->with('success', "Invitation resent successfully to {$invitation->email}.");
    }
}
