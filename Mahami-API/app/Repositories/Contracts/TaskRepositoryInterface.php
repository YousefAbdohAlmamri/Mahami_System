<?php

namespace App\Repositories\Contracts;

use App\Models\Task;

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
}

