<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\BoardList;
use App\Models\User;

class BoardListPolicy
{
    /**
     * Determine whether the user can view the list.
     */
    public function view(User $user, BoardList $list): bool
    {
        return (int) $user->id === (int) $list->board->user_id;
    }

    /**
     * Determine whether the user can create a list in the board.
     */
    public function create(User $user, Board $board): bool
    {
        return (int) $user->id === (int) $board->user_id;
    }

    /**
     * Determine whether the user can update the list.
     */
    public function update(User $user, BoardList $list): bool
    {
        return (int) $user->id === (int) $list->board->user_id;
    }

    /**
     * Determine whether the user can delete the list.
     */
    public function delete(User $user, BoardList $list): bool
    {
        return (int) $user->id === (int) $list->board->user_id;
    }

    /**
     * Determine whether the user can reorder lists in the board.
     */
    public function reorder(User $user, Board $board): bool
    {
        return (int) $user->id === (int) $board->user_id;
    }
}

