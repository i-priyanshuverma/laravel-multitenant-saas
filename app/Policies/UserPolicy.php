<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the current user can delete the target team member.
     */
    public function delete(User $currentUser, User $user): bool
    {
        // Must belong to the same tenant
        if ($currentUser->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Cannot remove the workspace owner
        $targetRole = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;
        if ($targetRole === 'owner') {
            return false;
        }

        // Only owners and admins can remove team members
        $currentRole = $currentUser->role instanceof UserRole ? $currentUser->role->value : (string) $currentUser->role;
        if (! in_array($currentRole, ['owner', 'admin'], true)) {
            return false;
        }

        // Cannot delete self via team roster
        if ($currentUser->id === $user->id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the current user can update the target team member's role.
     */
    public function update(User $currentUser, User $user): bool
    {
        // Must belong to the same tenant
        if ($currentUser->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Only owners and admins can update member roles
        if (! $currentUser->isElevated()) {
            return false;
        }

        // Cannot modify workspace owner
        if ($user->isOwner()) {
            return false;
        }

        // Cannot modify own role
        if ($currentUser->id === $user->id) {
            return false;
        }

        return true;
    }
}
