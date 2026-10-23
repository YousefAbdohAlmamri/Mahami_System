<?php

namespace App\Repositories\Contracts;

use App\Models\Task;
use App\Models\TaskChecklist;
use App\Models\TaskChecklistItem;

interface TaskChecklistRepositoryInterface
{
    /**
     * Create a checklist at the end of the task's checklists (items relation loaded).
     */
    public function createChecklist(Task $task, string $title): TaskChecklist;

    /**
     * Update a checklist (items relation loaded on the result).
     */
    public function updateChecklist(TaskChecklist $checklist, array $data): TaskChecklist;

    public function deleteChecklist(TaskChecklist $checklist): bool;

    /**
     * Add a not-completed item at the end of the checklist.
     */
    public function addItem(TaskChecklist $checklist, string $title): TaskChecklistItem;

    public function updateItem(TaskChecklistItem $item, array $data): TaskChecklistItem;

    public function deleteItem(TaskChecklistItem $item): bool;
}
