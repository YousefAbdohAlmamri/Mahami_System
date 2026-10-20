<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    /**
     * Determine whether the user can view the workspace.
     */
    public function view(User $user, Workspace $workspace): bool
    {
        return $workspace->owner_id === $user->id
            || $workspace->members()->where('users.id', $user->id)->where('status', 'active')->exists();
    }

    /**
     * Determine whether the user can update the workspace.
     */
    public function update(User $user, Workspace $workspace): bool
    {
        return $workspace->owner_id === $user->id
            || $workspace->hasMemberRole($user, ['owner', 'admin']);
    }

    /**
     * Determine whether the user can delete the workspace.
     */
    public function delete(User $user, Workspace $workspace): bool
    {
        return $workspace->owner_id === $user->id;
    }

    /**
     * Determine whether the user can manage members and invites.
     */
    public function manageMembers(User $user, Workspace $workspace): bool
    {
        return $workspace->owner_id === $user->id
            || $workspace->hasMemberRole($user, ['owner', 'admin']);
    }

    /**
     * Determine whether the user can transfer ownership.
     */
    public function transferOwnership(User $user, Workspace $workspace): bool
    {
        return $workspace->owner_id === $user->id;
    }

    /**
     * Determine whether the user can create boards within this workspace.
     */
    public function createBoard(User $user, Workspace $workspace): bool
    {
        return $workspace->owner_id === $user->id
            || $workspace->hasMemberRole($user, ['owner', 'admin', 'member']);
    }
}

