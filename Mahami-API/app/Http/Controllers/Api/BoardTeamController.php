<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignBoardTeamRequest;
use App\Http\Resources\BoardTeamResource;
use App\Models\Board;
use App\Models\Team;
use App\Repositories\Contracts\TeamRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class BoardTeamController extends Controller
{
    public function __construct(
        protected TeamRepositoryInterface $teamRepository
    ) {}

    public function index(Board $board): AnonymousResourceCollection
    {
        Gate::authorize('view', $board);

        return BoardTeamResource::collection($this->teamRepository->getForBoard($board));
    }

    public function store(AssignBoardTeamRequest $request, Board $board): JsonResponse
    {
        Gate::authorize('manageTeams', $board);

        $teamId = (int) $request->validated('team_id');
        $permission = $request->validated('permission', 'editor');

        $team = $this->teamRepository->findOrFail($teamId);

        // Verify team belongs to the board's workspace
        if ((int) $team->workspace_id !== (int) $board->workspace_id) {
            return response()->json([
                'message' => 'Team must belong to the same workspace as the board.',
            ], 422);
        }

        $this->teamRepository->assignBoard($team->id, $board->id, $permission);

        $assigned = $this->teamRepository->findBoardTeam($board, (int) $team->id);

        return response()->json([
            'message' => 'Team assigned to board successfully.',
            'data' => new BoardTeamResource($assigned),
        ], 201);
    }

    public function update(Request $request, Board $board, Team $team): JsonResponse
    {
        Gate::authorize('manageTeams', $board);

        $validated = $request->validate([
            'permission' => ['required', 'string', 'in:viewer,editor,admin'],
        ]);

        $this->teamRepository->assignBoard($team->id, $board->id, $validated['permission']);

        $assigned = $this->teamRepository->findBoardTeam($board, (int) $team->id);

        return response()->json([
            'message' => 'Team board permission updated successfully.',
            'data' => new BoardTeamResource($assigned),
        ]);
    }

    public function destroy(Board $board, Team $team): JsonResponse
    {
        Gate::authorize('manageTeams', $board);

        $this->teamRepository->removeBoard($team->id, $board->id);

        return response()->json([
            'message' => 'Team removed from board successfully.',
        ]);
    }
}
