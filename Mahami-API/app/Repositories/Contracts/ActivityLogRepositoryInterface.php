<?php

namespace App\Repositories\Contracts;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Collection;

interface ActivityLogRepositoryInterface
{
    public function create(array $data): ActivityLog;

    public function latestForTask(int $taskId, int $limit = 50): Collection;

    public function latestForBoard(int $boardId, int $limit = 50): Collection;
}
