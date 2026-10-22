<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;

class TaskCommentPolicy
{
    /**
     * Determine whether the user can view comments on the task.
     */
    public function viewAny(User $user, Task $task): bool
    {
        $board = $task->list?->board;
        if (!$board) return false;

        if ($board->user_id === $user->id) return true;

        if ($board->workspace_id) {
            return $board->getUserPermission($user) !== null;
        }

        return false;
    }

    /**
     * Determine whether the user can create comments on the task.
     */
    public function create(User $user, Task $task): bool
    {
        $board = $task->list?->board;
        if (!$board) return false;

        if ($board->user_id === $user->id) return true;

        if ($board->workspace_id) {
            $perm = $board->getUserPermission($user);
            return in_array($perm, ['editor', 'admin'], true);
        }

        return false;
    }

    /**
     * Determine whether the user can update the comment.
     */
    public function update(User $user, TaskComment $comment): bool
    {
        return $comment->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the comment.
     */
    public function delete(User $user, TaskComment $comment): bool
    {
        if ($comment->user_id === $user->id) return true;

        $board = $comment->task?->list?->board;
        if (!$board) return false;

        if ($board->user_id === $user->id) return true;

        if ($board->workspace_id) {
            return $board->getUserPermission($user) === 'admin';
        }

        return false;
    }
}

