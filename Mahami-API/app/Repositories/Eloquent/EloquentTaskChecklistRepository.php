<?php

namespace App\Repositories\Eloquent;

use App\Models\Task;
use App\Models\TaskChecklist;
use App\Models\TaskChecklistItem;
use App\Repositories\Contracts\TaskChecklistRepositoryInterface;

class EloquentTaskChecklistRepository implements TaskChecklistRepositoryInterface
{
    public function createChecklist(Task $task, string $title): TaskChecklist
    {
        $maxPos = $task->checklists()->max('position') ?? -1;

        $checklist = $task->checklists()->create([
            'title' => $title,
            'position' => $maxPos + 1,
        ]);

        return $checklist->load('items');
    }

    public function updateChecklist(TaskChecklist $checklist, array $data): TaskChecklist
    {
        $checklist->update($data);

        return $checklist->load('items');
    }

    public function deleteChecklist(TaskChecklist $checklist): bool
    {
        return (bool) $checklist->delete();
    }

    public function addItem(TaskChecklist $checklist, string $title): TaskChecklistItem
    {
        $maxPos = $checklist->items()->max('position') ?? -1;

        return $checklist->items()->create([
            'title' => $title,
            'is_completed' => false,
            'position' => $maxPos + 1,
        ]);
    }

    public function updateItem(TaskChecklistItem $item, array $data): TaskChecklistItem
    {
        $item->update($data);

        return $item;
    }

    public function deleteItem(TaskChecklistItem $item): bool
    {
        return (bool) $item->delete();
    }
}
