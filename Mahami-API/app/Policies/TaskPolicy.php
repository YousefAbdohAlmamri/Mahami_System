<?php

namespace App\Policies;

use App\Models\BoardList;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Determine whether the user can view the task.
     */
    public function view(User $user, Task $task): bool
    {
        $board = $task->list?->board;
        if (!$board) return false;

        if ((int) $user->id === (int) $board->user_id) {
            return true;
        }

        if ($board->workspace_id) {
            return $board->getUserPermission($user) !== null;
        }

        return false;
    }

    /**
     * Determine whether the user can create tasks in the list.
     */
    public function create(User $user, BoardList $list): bool
    {
        $board = $list->board;
        if (!$board) return false;

        if ((int) $user->id === (int) $board->user_id) {
            return true;
        }

        if ($board->workspace_id) {
            $perm = $board->getUserPermission($user);
            return in_array($perm, ['editor', 'admin'], true);
        }

        return false;
    }

    /**
     * Determine whether the user can update the task.
     */
    public function update(User $user, Task $task): bool
    {
        $board = $task->list?->board;
        if (!$board) return false;

        if ((int) $user->id === (int) $board->user_id) {
            return true;
        }

        if ($board->workspace_id) {
            $perm = $board->getUserPermission($user);
            return in_array($perm, ['editor', 'admin'], true);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the task.
     */
    public function delete(User $user, Task $task): bool
    {
        $board = $task->list?->board;
        if (!$board) return false;

        if ((int) $user->id === (int) $board->user_id) {
            return true;
        }

        if ($board->workspace_id) {
            $perm = $board->getUserPermission($user);
            return in_array($perm, ['editor', 'admin'], true);
        }

        return false;
    }

    /**
     * Determine whether the user can move the task to a target list.
     * Both the task and the target list MUST belong to the user and the same board.
     */
    public function move(User $user, Task $task, BoardList $targetList): bool
    {
        $board = $task->list?->board;
        if (!$board) return false;

        $hasAccess = (int) $user->id === (int) $board->user_id ||
            ($board->workspace_id && in_array($board->getUserPermission($user), ['editor', 'admin'], true));

        $sameBoard = (int) $task->list->board_id === (int) $targetList->board_id;

        return $hasAccess && $sameBoard;
    }

    /**
     * Determine whether the user can reorder tasks within the list.
     */
    public function reorder(User $user, BoardList $list): bool
    {
        $board = $list->board;
        if (!$board) return false;

        if ((int) $user->id === (int) $board->user_id) {
            return true;
        }

        if ($board->workspace_id) {
            $perm = $board->getUserPermission($user);
            return in_array($perm, ['editor', 'admin'], true);
        }

        return false;
    }
}
