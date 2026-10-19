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
        return (int) $user->id === (int) $task->list->board->user_id;
    }

    /**
     * Determine whether the user can create tasks in the list.
     */
    public function create(User $user, BoardList $list): bool
    {
        return (int) $user->id === (int) $list->board->user_id;
    }

    /**
     * Determine whether the user can update the task.
     */
    public function update(User $user, Task $task): bool
    {
        return (int) $user->id === (int) $task->list->board->user_id;
    }

    /**
     * Determine whether the user can delete the task.
     */
    public function delete(User $user, Task $task): bool
    {
        return (int) $user->id === (int) $task->list->board->user_id;
    }

    /**
     * Determine whether the user can move the task to a target list.
     * Both the task and the target list MUST belong to the user and the same board.
     */
    public function move(User $user, Task $task, BoardList $targetList): bool
    {
        $ownsSource = (int) $user->id === (int) $task->list->board->user_id;
        $sameBoard = (int) $task->list->board_id === (int) $targetList->board_id;

        return $ownsSource && $sameBoard;
    }

    /**
     * Determine whether the user can reorder tasks within the list.
     */
    public function reorder(User $user, BoardList $list): bool
    {
        return (int) $user->id === (int) $list->board->user_id;
    }
}
