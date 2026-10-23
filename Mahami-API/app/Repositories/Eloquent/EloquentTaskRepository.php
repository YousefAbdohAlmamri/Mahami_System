<?php

namespace App\Repositories\Eloquent;

use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

class EloquentTaskRepository implements TaskRepositoryInterface
{
    public function __construct(
        protected Task $model
    ) {}

    public function findById(int $id): ?Task
    {
        return $this->model->find($id);
    }

    public function findWithListAndBoard(int $id): ?Task
    {
        return $this->model
            ->with(['list.board'])
            ->find($id);
    }

    public function create(array $data): Task
    {
        if (!isset($data['position'])) {
            $data['position'] = $this->getNextPosition((int) $data['board_list_id']);
        }

        return $this->model->create($data);
    }

    public function update(Task $task, array $data): Task
    {
        $task->update($data);
        return $task->fresh();
    }

    public function delete(Task $task): bool
    {
        return DB::transaction(function () use ($task) {
            $listId = (int) $task->board_list_id;
            $position = (int) $task->position;

            $deleted = $task->delete();

            if ($deleted) {
                // Re-compact subsequent tasks to keep contiguous 0-based positions
                $this->model
                    ->where('board_list_id', $listId)
                    ->where('position', '>', $position)
                    ->decrement('position');
            }

            return (bool) $deleted;
        });
    }

    public function move(Task $task, int $targetListId, int $position): Task
    {
        return DB::transaction(function () use ($task, $targetListId, $position) {
            $sourceListId = (int) $task->board_list_id;
            $oldPosition = (int) $task->position;

            if ($sourceListId === $targetListId) {
                if ($oldPosition < $position) {
                    $this->model
                        ->where('board_list_id', $sourceListId)
                        ->where('id', '!=', $task->id)
                        ->where('position', '>', $oldPosition)
                        ->where('position', '<=', $position)
                        ->decrement('position');
                } elseif ($oldPosition > $position) {
                    $this->model
                        ->where('board_list_id', $sourceListId)
                        ->where('id', '!=', $task->id)
                        ->where('position', '>=', $position)
                        ->where('position', '<', $oldPosition)
                        ->increment('position');
                }
            } else {
                // Adjust old list
                $this->model
                    ->where('board_list_id', $sourceListId)
                    ->where('position', '>', $oldPosition)
                    ->decrement('position');

                // Make room in target list
                $this->model
                    ->where('board_list_id', $targetListId)
                    ->where('position', '>=', $position)
                    ->increment('position');
            }

            $task->update([
                'board_list_id' => $targetListId,
                'position' => $position,
            ]);

            return $task->fresh();
        });
    }

    public function reorder(int $listId, array $orderedIds): bool
    {
        return DB::transaction(function () use ($listId, $orderedIds) {
            foreach ($orderedIds as $index => $id) {
                $this->model
                    ->where('board_list_id', $listId)
                    ->where('id', $id)
                    ->update(['position' => $index]);
            }
            return true;
        });
    }

    public function getNextPosition(int $listId): int
    {
        $max = $this->model->where('board_list_id', $listId)->max('position');
        return is_null($max) ? 0 : (int) $max + 1;
    }

    public function findOrFailWithContext(int $id): Task
    {
        return $this->model
            ->with('list.board.workspace')
            ->findOrFail($id);
    }

    public function getForList(int $listId): Collection
    {
        return $this->model
            ->where('board_list_id', $listId)
            ->orderBy('position')
            ->get();
    }

    public function countInList(int $listId, array $ids): int
    {
        return $this->model
            ->where('board_list_id', $listId)
            ->whereIn('id', $ids)
            ->count();
    }

    public function getDueOnOrBefore(string $date): Collection
    {
        return $this->model
            ->whereNotNull('due_date')
            ->where('due_date', '<=', $date)
            ->with(['assignees', 'list.board'])
            ->get();
    }

    public function assigneeIds(Task $task): array
    {
        return $task->assignees()->pluck('users.id')->map(fn ($id) => (int) $id)->all();
    }

    public function getAssignees(Task $task): Collection
    {
        return $task->assignees;
    }

    public function syncAssignees(Task $task, array $userIds): void
    {
        $task->assignees()->sync($userIds);
    }

    public function addAssignee(Task $task, int $userId): void
    {
        $task->assignees()->syncWithoutDetaching([$userId]);
    }

    public function removeAssignee(Task $task, int $userId): void
    {
        $task->assignees()->detach($userId);
    }

    public function syncLabels(Task $task, array $labelIds): void
    {
        $task->labels()->sync($labelIds);
    }

    public function attachLabel(Task $task, int $labelId): void
    {
        $task->labels()->syncWithoutDetaching([$labelId]);
    }

    public function detachLabel(Task $task, int $labelId): void
    {
        $task->labels()->detach($labelId);
    }

    public function refreshWithDetails(Task $task): ?Task
    {
        return $task->fresh(['assignees', 'labels', 'checklists.items', 'attachments']);
    }
}
