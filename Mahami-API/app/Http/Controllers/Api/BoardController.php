<?php

namespace App\Http\Controllers\Api;

use App\Actions\Boards\CreateBoardAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Boards\StoreBoardRequest;
use App\Http\Requests\Boards\UpdateBoardRequest;
use App\Http\Resources\BoardResource;
use App\Models\Board;
use App\Repositories\Contracts\BoardRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class BoardController extends Controller
{
    public function __construct(
        protected BoardRepositoryInterface $boardRepository
    ) {}

    /**
     * Display a listing of boards owned by the authenticated user.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Board::class);

        $workspaceId = $request->has('workspace_id') ? (int) $request->input('workspace_id') : null;
        $boards = $this->boardRepository->getForUser((int) $request->user()->id, $workspaceId);

        return BoardResource::collection($boards);
    }

    /**
     * Store a newly created board along with default columns.
     */
    public function store(StoreBoardRequest $request, CreateBoardAction $createBoardAction): JsonResponse
    {
        Gate::authorize('create', Board::class);

        $board = $createBoardAction->execute($request->user(), $request->validated());

        return (new BoardResource($board))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified board with lists and tasks.
     */
    public function show(Request $request, Board $board): BoardResource
    {
        Gate::authorize('view', $board);

        $boardWithRelations = $this->boardRepository->findWithListsAndTasks(
            (int) $board->id,
            (int) $request->user()->id
        );

        return new BoardResource($boardWithRelations ?? $board);
    }

    /**
     * Update the specified board in storage.
     */
    public function update(UpdateBoardRequest $request, Board $board): BoardResource
    {
        Gate::authorize('update', $board);

        $updatedBoard = $this->boardRepository->update($board, $request->validated());

        return new BoardResource($updatedBoard);
    }

    /**
     * Remove the specified board from storage.
     */
    public function destroy(Request $request, Board $board): JsonResponse
    {
        Gate::authorize('delete', $board);

        $this->boardRepository->delete($board);

        return response()->json([
            'message' => 'Board deleted successfully.',
        ]);
    }
}

