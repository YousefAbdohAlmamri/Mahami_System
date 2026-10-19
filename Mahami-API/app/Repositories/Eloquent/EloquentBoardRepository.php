<?php

namespace App\Repositories\Eloquent;

use App\Models\Board;
use App\Repositories\Contracts\BoardRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentBoardRepository implements BoardRepositoryInterface
{
    public function __construct(
        protected Board $model
    ) {}

    public function getForUser(int $userId): Collection
    {
        return $this->model
            ->where('user_id', $userId)
            ->withCount('lists')
            ->latest()
            ->get();
    }

    public function findByIdForUser(int $id, int $userId): ?Board
    {
        return $this->model
            ->where('user_id', $userId)
            ->find($id);
    }

    public function findWithListsAndTasks(int $id, int $userId): ?Board
    {
        return $this->model
            ->where('user_id', $userId)
            ->with([
                'lists' => function ($query) {
                    $query->orderBy('position')->with([
                        'tasks' => function ($taskQuery) {
                            $taskQuery->orderBy('position');
                        }
                    ]);
                }
            ])
            ->find($id);
    }

    public function create(array $data): Board
    {
        return $this->model->create($data);
    }

    public function update(Board $board, array $data): Board
    {
        $board->update($data);
        return $board->fresh();
    }

    public function delete(Board $board): bool
    {
        return (bool) $board->delete();
    }
}

