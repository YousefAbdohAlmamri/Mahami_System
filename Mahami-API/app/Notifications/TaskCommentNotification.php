<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskCommentNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Task $task,
        public TaskComment $comment,
        public User $commenter,
        public bool $isMention = false
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $message = $this->isMention
            ? "أشار إليك {$this->commenter->name} في تعليق على المهمة: \"{$this->task->title}\""
            : "أضاف {$this->commenter->name} تعليقاً على المهمة: \"{$this->task->title}\"";

        return [
            'type' => $this->isMention ? 'mention' : 'task_comment',
            'task_id' => $this->task->id,
            'board_id' => $this->task->list?->board_id,
            'comment_id' => $this->comment->id,
            'actor' => [
                'id' => $this->commenter->id,
                'name' => $this->commenter->name,
            ],
            'message' => $message,
            'created_at' => now()->toISOString(),
        ];
    }
}

