<?php

namespace App\Repositories\Eloquent;

use App\Models\Task;
use App\Models\TaskComment;
use App\Repositories\Contracts\TaskCommentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentTaskCommentRepository implements TaskCommentRepositoryInterface
{
    public function forTask(Task $task): Collection
    {
        return $task->comments()->with('user')->oldest()->get();
    }

    public function findOrFailWithContext(int $id): TaskComment
    {
        return TaskComment::with('task.list.board.workspace')->findOrFail($id);
    }

    public function create(Task $task, int $userId, string $content): TaskComment
    {
        return $task->comments()->create([
            'user_id' => $userId,
            'content' => $content,
        ]);
    }

    public function update(TaskComment $comment, string $content): TaskComment
    {
        $comment->update([
            'content' => $content,
        ]);

        return $comment->load('user');
    }

    public function delete(TaskComment $comment): bool
    {
        return (bool) $comment->delete();
    }

    public function loadAuthor(TaskComment $comment): TaskComment
    {
        return $comment->load('user');
    }
}
