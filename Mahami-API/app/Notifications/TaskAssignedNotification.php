<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Task $task,
        public User $assigner
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'task_assigned',
            'task_id' => $this->task->id,
            'board_id' => $this->task->list?->board_id,
            'task_title' => $this->task->title,
            'actor' => [
                'id' => $this->assigner->id,
                'name' => $this->assigner->name,
            ],
            'message' => "تم تعيينك في المهمة: \"{$this->task->title}\" بواسطة {$this->assigner->name}",
            'created_at' => now()->toISOString(),
        ];
    }
}

