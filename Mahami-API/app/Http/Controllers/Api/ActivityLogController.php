<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use App\Models\Board;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActivityLogController extends Controller
{
    /**
     * Get activity log for a specific task.
     */
    public function taskActivities(Request $request, int $taskId): AnonymousResourceCollection
    {
        $task = Task::with('list.board.workspace')->findOrFail($taskId);
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

        $activities = ActivityLog::where('task_id', $task->id)
            ->with('user')
            ->latest('created_at')
            ->limit(50)
            ->get();

        return ActivityLogResource::collection($activities);
    }

    /**
     * Get recent activity logs for a whole board.
     */
    public function boardActivities(Request $request, int $boardId): AnonymousResourceCollection
    {
        $board = Board::with('workspace')->findOrFail($boardId);
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

        $activities = ActivityLog::where('board_id', $board->id)
            ->with('user')
            ->latest('created_at')
            ->limit(50)
            ->get();

        return ActivityLogResource::collection($activities);
    }
}
