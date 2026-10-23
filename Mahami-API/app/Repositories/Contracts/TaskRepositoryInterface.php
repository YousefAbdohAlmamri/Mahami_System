<?php

namespace App\Repositories\Contracts;

use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;

interface TaskRepositoryInterface
{
    /**
     * Find a task by its ID.
     *
     * @param int $id
     * @return Task|null
     */
    public function findById(int $id): ?Task;

    /**
     * Find a task with its list and board eager loaded.
     *
     * @param int $id
     * @return Task|null
     */
    public function findWithListAndBoard(int $id): ?Task;

    /**
     * Create a new task record.
     *
     * @param array<string, mixed> $data
     * @return Task
     */
    public function create(array $data): Task;

    /**
     * Update an existing task record.
     *
     * @param Task $task
     * @param array<string, mixed> $data
     * @return Task
     */
    public function update(Task $task, array $data): Task;

    /**
     * Delete an existing task record.
     *
     * @param Task $task
     * @return bool
     */
    public function delete(Task $task): bool;

    /**
     * Move a task to a different list (or same list) and position.
     *
     * @param Task $task
     * @param int $targetListId
     * @param int $position
     * @return Task
     */
    public function move(Task $task, int $targetListId, int $position): Task;

    /**
     * Reorder tasks within a list.
     *
     * @param int $listId
     * @param array<int, int> $orderedIds Array of task IDs in new order
     * @return bool
     */
    public function reorder(int $listId, array $orderedIds): bool;

    /**
     * Get the next position value for a task within a list.
     *
     * @param int $listId
     * @return int
     */
    public function getNextPosition(int $listId): int;

    /**
     * Find a task with its list, board and workspace or fail with a 404.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFailWithContext(int $id): Task;

    /**
     * Tasks of a list ordered by position.
     */
    public function getForList(int $listId): Collection;

    /**
     * Count how many of the given task ids belong to the list.
     *
     * @param  array<int, int|string>  $ids
     */
    public function countInList(int $listId, array $ids): int;

    /**
     * Tasks whose due date is on or before the given date (Y-m-d),
     * with assignees and list.board loaded.
     */
    public function getDueOnOrBefore(string $date): Collection;

    /**
     * Current assignee ids of the task.
     *
     * @return array<int, int>
     */
    public function assigneeIds(Task $task): array;

    /**
     * Assignees of the task (loads the relation if needed).
     */
    public function getAssignees(Task $task): Collection;

    /**
     * @param  array<int, int>  $userIds
     */
    public function syncAssignees(Task $task, array $userIds): void;

    public function addAssignee(Task $task, int $userId): void;

    public function removeAssignee(Task $task, int $userId): void;

    /**
     * @param  array<int, int|string>  $labelIds
     */
    public function syncLabels(Task $task, array $labelIds): void;

    public function attachLabel(Task $task, int $labelId): void;

    public function detachLabel(Task $task, int $labelId): void;

    /**
     * Reload the task with assignees, labels, checklists.items and attachments.
     */
    public function refreshWithDetails(Task $task): ?Task;
}
