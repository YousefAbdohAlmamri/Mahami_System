<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\BoardRepositoryInterface;
use App\Repositories\Contracts\WorkspaceRepositoryInterface;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(
        protected AnalyticsService $analyticsService,
        protected BoardRepositoryInterface $boardRepository,
        protected WorkspaceRepositoryInterface $workspaceRepository
    ) {}

    /**
     * Get analytics for a board.
     */
    public function board(Request $request, int $boardId): JsonResponse
    {
        $board = $this->boardRepository->findOrFailWithWorkspace($boardId);
        $user = $request->user();

        if ($board->user_id !== $user->id) {
            if ($board->workspace_id) {
                if ($board->getUserPermission($user) === null) {
                    abort(403, 'Unauthorized access to board analytics.');
                }
            } else {
                abort(403, 'Unauthorized access to board analytics.');
            }
        }

        $analytics = $this->analyticsService->getBoardAnalytics($board);

        return response()->json([
            'data' => $analytics,
        ]);
    }

    /**
     * Get analytics for a workspace.
     */
    public function workspace(Request $request, int $workspaceId): JsonResponse
    {
        $workspace = $this->workspaceRepository->findOrFail($workspaceId);
        $user = $request->user();

        if ($workspace->owner_id !== $user->id && !$workspace->hasMemberRole($user, ['owner', 'admin', 'member'])) {
            abort(403, 'Unauthorized access to workspace analytics.');
        }

        $analytics = $this->analyticsService->getWorkspaceAnalytics($workspace);

        return response()->json([
            'data' => $analytics,
        ]);
    }
}
