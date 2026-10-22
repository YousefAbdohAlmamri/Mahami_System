<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskDueReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Task $task,
        public bool $isOverdue = false
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $dueDate = $this->task->due_date?->format('Y-m-d');
        $message = $this->isOverdue
            ? "تنبيه: المهمة \"{$this->task->title}\" تجاوزت موعد الاستحقاق ({$dueDate})"
            : "تنبيه: المهمة \"{$this->task->title}\" تستحق اليوم ({$dueDate})";

        return [
            'type' => 'task_due_reminder',
            'task_id' => $this->task->id,
            'board_id' => $this->task->list?->board_id,
            'task_title' => $this->task->title,
            'due_date' => $dueDate,
            'is_overdue' => $this->isOverdue,
            'message' => $message,
            'created_at' => now()->toISOString(),
        ];
    }
}

