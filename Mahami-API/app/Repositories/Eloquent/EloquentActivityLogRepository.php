<?php

namespace App\Repositories\Eloquent;

use App\Models\ActivityLog;
use App\Repositories\Contracts\ActivityLogRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentActivityLogRepository implements ActivityLogRepositoryInterface
{
    public function create(array $data): ActivityLog
    {
        return ActivityLog::create($data);
    }

    public function latestForTask(int $taskId, int $limit = 50): Collection
    {
        return ActivityLog::where('task_id', $taskId)
            ->with('user')
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    public function latestForBoard(int $boardId, int $limit = 50): Collection
    {
        return ActivityLog::where('board_id', $boardId)
            ->with('user')
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }
}
