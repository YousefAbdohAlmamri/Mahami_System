<?php

namespace App\Console\Commands;

use App\Notifications\TaskDueReminderNotification;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Console\Command;

class SendTaskDueReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mahami:send-due-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications to assignees for tasks due today or overdue';

    /**
     * Execute the console command.
     */
    public function handle(TaskRepositoryInterface $taskRepository): int
    {
        $today = now()->format('Y-m-d');

        $tasks = $taskRepository->getDueOnOrBefore($today);

        $notificationsSent = 0;

        foreach ($tasks as $task) {
            $isOverdue = $task->due_date->format('Y-m-d') < $today;

            foreach ($task->assignees as $assignee) {
                $assignee->notify(new TaskDueReminderNotification($task, $isOverdue));
                $notificationsSent++;
            }
        }

        $this->info("Successfully sent {$notificationsSent} due date reminders.");

        return Command::SUCCESS;
    }
}

