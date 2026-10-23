<?php

namespace App\Repositories\Eloquent;

use App\Models\BoardList;
use App\Repositories\Contracts\BoardListRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class EloquentBoardListRepository implements BoardListRepositoryInterface
{
    public function __construct(
        protected BoardList $model
    ) {}

    public function getForBoard(int $boardId): Collection
    {
        return $this->model
            ->where('board_id', $boardId)
            ->withCount('tasks')
            ->orderBy('position')
            ->get();
    }

    public function findByIdForBoard(int $id, int $boardId): ?BoardList
    {
        return $this->model
            ->where('board_id', $boardId)
            ->find($id);
    }

    public function create(array $data): BoardList
    {
        if (!isset($data['position'])) {
            $data['position'] = $this->getNextPosition((int) $data['board_id']);
        }

        return $this->model->create($data);
    }

    public function update(BoardList $list, array $data): BoardList
    {
        $list->update($data);
        return $list->fresh();
    }

    public function delete(BoardList $list): bool
    {
        return (bool) $list->delete();
    }

    public function reorder(int $boardId, array $orderedIds): bool
    {
        return DB::transaction(function () use ($boardId, $orderedIds) {
            foreach ($orderedIds as $index => $id) {
                $this->model
                    ->where('board_id', $boardId)
                    ->where('id', $id)
                    ->update(['position' => $index]);
            }
            return true;
        });
    }

    public function getNextPosition(int $boardId): int
    {
        $max = $this->model->where('board_id', $boardId)->max('position');
        return is_null($max) ? 0 : (int) $max + 1;
    }

    public function find(int $id): ?BoardList
    {
        return $this->model->find($id);
    }

    public function findOrFail(int $id): BoardList
    {
        return $this->model->findOrFail($id);
    }

    public function countInBoard(int $boardId, array $ids): int
    {
        return $this->model
            ->where('board_id', $boardId)
            ->whereIn('id', $ids)
            ->count();
    }
}
