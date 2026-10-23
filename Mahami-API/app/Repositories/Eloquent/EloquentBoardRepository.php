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

    public function getForUser(int $userId, ?int $workspaceId = null): Collection
    {
        $query = $this->model->newQuery();

        if ($workspaceId) {
            $query->where('workspace_id', $workspaceId)
                ->where(function ($q) use ($userId) {
                    $q->where('user_id', $userId)
                      ->orWhereHas('workspace.members', function ($mq) use ($userId) {
                          $mq->where('users.id', $userId)->where('status', 'active');
                      });
                });
        } else {
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereHas('workspace.members', function ($mq) use ($userId) {
                      $mq->where('users.id', $userId)->where('status', 'active');
                  });
            });
        }

        return $query->withCount('lists')->latest()->get();
    }

    public function findByIdForUser(int $id, int $userId): ?Board
    {
        return $this->model
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereHas('workspace.members', function ($mq) use ($userId) {
                      $mq->where('users.id', $userId)->where('status', 'active');
                  });
            })
            ->find($id);
    }

    public function findWithListsAndTasks(int $id, int $userId): ?Board
    {
        return $this->model
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereHas('workspace.members', function ($mq) use ($userId) {
                      $mq->where('users.id', $userId)->where('status', 'active');
                  });
            })
            ->with([
                'labels',
                'lists' => function ($query) {
                    $query->orderBy('position')->with([
                        'tasks' => function ($taskQuery) {
                            $taskQuery->orderBy('position')
                                ->with(['assignees', 'labels', 'checklists.items', 'attachments', 'comments']);
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

    public function findOrFailWithWorkspace(int $id): Board
    {
        return $this->model->with('workspace')->findOrFail($id);
    }

    public function loadForAnalytics(Board $board): Board
    {
        return $board->load(['lists.tasks.assignees', 'lists.tasks.checklists.items']);
    }
}
