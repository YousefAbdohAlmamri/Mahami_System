<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Board;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Contracts\ActivityLogRepositoryInterface;

class ActivityLogger
{
    /**
     * Log an action performed on a board or task.
     */
    public static function log(
        Board $board,
        string $action,
        string $description,
        ?Task $task = null,
        ?User $user = null,
        array $properties = []
    ): ActivityLog {
        $user = $user ?? auth()->user();

        return app(ActivityLogRepositoryInterface::class)->create([
            'workspace_id' => $board->workspace_id,
            'board_id' => $board->id,
            'task_id' => $task?->id,
            'user_id' => $user?->id,
            'action' => $action,
            'description' => $description,
            'properties' => !empty($properties) ? $properties : null,
            'created_at' => now(),
        ]);
    }
}

