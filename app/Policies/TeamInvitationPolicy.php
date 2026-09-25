<?php

namespace App\Policies;

use App\Models\TeamInvitation;
use App\Models\User;

class TeamInvitationPolicy
{
    /**
     * Determine whether the user can create invitations.
     */
    public function create(User $user): bool
    {
        return $user->isElevated();
    }

    /**
     * Determine whether the user can delete/revoke the invitation.
     */
    public function delete(User $user, TeamInvitation $invitation): bool
    {
        return $user->tenant_id === $invitation->tenant_id && $user->isElevated();
    }

    /**
     * Determine whether the user can resend the invitation.
     */
    public function resend(User $user, TeamInvitation $invitation): bool
    {
        return $user->tenant_id === $invitation->tenant_id && $user->isElevated();
    }
}
