<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Repositories\Contracts\ActivityLogRepositoryInterface;
use App\Repositories\Contracts\BoardRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActivityLogController extends Controller
{
    public function __construct(
        protected ActivityLogRepositoryInterface $activityLogRepository,
        protected TaskRepositoryInterface $taskRepository,
        protected BoardRepositoryInterface $boardRepository
    ) {}

    /**
     * Get the activity log for a specific task (latest 50).
     */
    public function taskActivities(Request $request, int $taskId): AnonymousResourceCollection
    {
        $task = $this->taskRepository->findOrFailWithContext($taskId);
        $board = $task->list?->board;

        if (!$board) {
            abort(404, 'Board not found');
        }

        $user = $request->user();

        if ($board->user_id !== $user->id) {
            if ($board->workspace_id) {
                if ($board->getUserPermission($user) === null) {
                    abort(403, 'Unauthorized');
                }
            } else {
                abort(403, 'Unauthorized');
            }
        }

        $activities = $this->activityLogRepository->latestForTask((int) $task->id, 50);

        return ActivityLogResource::collection($activities);
    }

    /**
     * Get the activity log for a board (latest 50).
     */
    public function boardActivities(Request $request, int $boardId): AnonymousResourceCollection
    {
        $board = $this->boardRepository->findOrFailWithWorkspace($boardId);
        $user = $request->user();

        if ($board->user_id !== $user->id) {
            if ($board->workspace_id) {
                if ($board->getUserPermission($user) === null) {
                    abort(403, 'Unauthorized');
                }
            } else {
                abort(403, 'Unauthorized');
            }
        }

        $activities = $this->activityLogRepository->latestForBoard((int) $board->id, 50);

        return ActivityLogResource::collection($activities);
    }
}
