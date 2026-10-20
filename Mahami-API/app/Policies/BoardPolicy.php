<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\User;

class BoardPolicy
{
    /**
     * Determine whether the user can view any boards.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the board.
     */
    public function view(User $user, Board $board): bool
    {
        if ((int) $user->id === (int) $board->user_id) {
            return true;
        }

        if ($board->workspace_id && $board->workspace) {
            $perm = $board->getUserPermission($user);
            return $perm !== null;
        }

        return false;
    }

    /**
     * Determine whether the user can create boards.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the board.
     */
    public function update(User $user, Board $board): bool
    {
        if ((int) $user->id === (int) $board->user_id) {
            return true;
        }

        if ($board->workspace_id && $board->workspace) {
            if ($board->workspace->hasMemberRole($user, ['owner', 'admin'])) {
                return true;
            }
            return $board->getUserPermission($user) === 'admin';
        }

        return false;
    }

    /**
     * Determine whether the user can delete the board.
     */
    public function delete(User $user, Board $board): bool
    {
        if ((int) $user->id === (int) $board->user_id) {
            return true;
        }

        if ($board->workspace_id && $board->workspace) {
            return (int) $board->workspace->owner_id === (int) $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can manage team assignments on this board.
     */
    public function manageTeams(User $user, Board $board): bool
    {
        return $this->update($user, $board);
    }
}
