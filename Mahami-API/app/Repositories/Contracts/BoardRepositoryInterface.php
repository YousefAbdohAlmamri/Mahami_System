<?php

namespace App\Repositories\Contracts;

use App\Models\Board;
use Illuminate\Database\Eloquent\Collection;

interface BoardRepositoryInterface
{
    /**
     * Get all boards owned by a specific user.
     *
     * @param int $userId
     * @return Collection<int, Board>
     */
    public function getForUser(int $userId): Collection;

    /**
     * Find a specific board for a user by its ID.
     *
     * @param int $id
     * @param int $userId
     * @return Board|null
     */
    public function findByIdForUser(int $id, int $userId): ?Board;

    /**
     * Find a board by its ID with eagerly loaded lists and tasks.
     *
     * @param int $id
     * @param int $userId
     * @return Board|null
     */
    public function findWithListsAndTasks(int $id, int $userId): ?Board;

    /**
     * Create a new board record.
     *
     * @param array<string, mixed> $data
     * @return Board
     */
    public function create(array $data): Board;

    /**
     * Update an existing board record.
     *
     * @param Board $board
     * @param array<string, mixed> $data
     * @return Board
     */
    public function update(Board $board, array $data): Board;

    /**
     * Delete an existing board record.
     *
     * @param Board $board
     * @return bool
     */
    public function delete(Board $board): bool;
}
