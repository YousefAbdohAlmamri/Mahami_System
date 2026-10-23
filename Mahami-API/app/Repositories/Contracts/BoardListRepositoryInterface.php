<?php

namespace App\Repositories\Contracts;

use App\Models\BoardList;
use Illuminate\Database\Eloquent\Collection;

interface BoardListRepositoryInterface
{
    /**
     * Get all lists for a specific board, ordered by position.
     *
     * @param int $boardId
     * @return Collection<int, BoardList>
     */
    public function getForBoard(int $boardId): Collection;

    /**
     * Find a list by ID for a specific board.
     *
     * @param int $id
     * @param int $boardId
     * @return BoardList|null
     */
    public function findByIdForBoard(int $id, int $boardId): ?BoardList;

    /**
     * Create a new board list record.
     *
     * @param array<string, mixed> $data
     * @return BoardList
     */
    public function create(array $data): BoardList;

    /**
     * Update an existing board list record.
     *
     * @param BoardList $list
     * @param array<string, mixed> $data
     * @return BoardList
     */
    public function update(BoardList $list, array $data): BoardList;

    /**
     * Delete an existing board list record.
     *
     * @param BoardList $list
     * @return bool
     */
    public function delete(BoardList $list): bool;

    /**
     * Reorder lists within a board.
     *
     * @param int $boardId
     * @param array<int, int> $orderedIds Array of list IDs in new order
     * @return bool
     */
    public function reorder(int $boardId, array $orderedIds): bool;

    /**
     * Get the next position value for a list within a board.
     *
     * @param int $boardId
     * @return int
     */
    public function getNextPosition(int $boardId): int;

    public function find(int $id): ?BoardList;

    /**
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): BoardList;

    /**
     * Count how many of the given list ids belong to the board.
     *
     * @param  array<int, int|string>  $ids
     */
    public function countInBoard(int $boardId, array $ids): int;
}
