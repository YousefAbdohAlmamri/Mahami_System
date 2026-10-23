<?php

namespace App\Repositories\Contracts;

use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Database\Eloquent\Collection;

interface TaskCommentRepositoryInterface
{
    /**
     * Comments of a task (oldest first) with their author.
     */
    public function forTask(Task $task): Collection;

    /**
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFailWithContext(int $id): TaskComment;

    public function create(Task $task, int $userId, string $content): TaskComment;

    public function update(TaskComment $comment, string $content): TaskComment;

    public function delete(TaskComment $comment): bool;

    public function loadAuthor(TaskComment $comment): TaskComment;
}
