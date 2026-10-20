<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;
use App\Models\Workspace;

class TeamPolicy
{
    /**
     * Determine whether the user can view teams in workspace.
     */
    public function viewAny(User $user, Workspace $workspace): bool
    {
        return $workspace->owner_id === $user->id || $workspace->hasMemberRole($user, ['owner', 'admin', 'member', 'guest']);
    }

    /**
     * Determine whether the user can view the team.
     */
    public function view(User $user, Team $team): bool
    {
        $ws = $team->workspace;
        if (!$ws) return false;
        return $ws->owner_id === $user->id || $ws->hasMemberRole($user, ['owner', 'admin', 'member', 'guest']);
    }

    /**
     * Determine whether the user can create teams in workspace.
     */
    public function create(User $user, Workspace $workspace): bool
    {
        return $workspace->owner_id === $user->id || $workspace->hasMemberRole($user, ['owner', 'admin']);
    }

    /**
     * Determine whether the user can update the team.
     */
    public function update(User $user, Team $team): bool
    {
        $ws = $team->workspace;
        if (!$ws) return false;
        if ($ws->owner_id === $user->id || $ws->hasMemberRole($user, ['owner', 'admin'])) {
            return true;
        }

        return $team->isLead($user);
    }

    /**
     * Determine whether the user can delete the team.
     */
    public function delete(User $user, Team $team): bool
    {
        $ws = $team->workspace;
        if (!$ws) return false;
        return $ws->owner_id === $user->id || $ws->hasMemberRole($user, ['owner', 'admin']);
    }

    /**
     * Determine whether the user can manage team members.
     */
    public function manageMembers(User $user, Team $team): bool
    {
        return $this->update($user, $team);
    }
}
