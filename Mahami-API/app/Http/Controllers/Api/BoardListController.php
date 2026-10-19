<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lists\ReorderBoardListsRequest;
use App\Http\Requests\Lists\StoreBoardListRequest;
use App\Http\Requests\Lists\UpdateBoardListRequest;
use App\Http\Resources\BoardListResource;
use App\Models\Board;
use App\Models\BoardList;
use App\Repositories\Contracts\BoardListRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class BoardListController extends Controller
{
    public function __construct(
        protected BoardListRepositoryInterface $boardListRepository
    ) {}

    /**
     * Store a newly created list within a board.
     */
    public function store(StoreBoardListRequest $request, Board $board): JsonResponse
    {
        Gate::authorize('create', [BoardList::class, $board]);

        $position = $this->boardListRepository->getNextPosition((int) $board->id);

        $list = $this->boardListRepository->create([
            'board_id' => $board->id,
            'title' => $request->validated('title'),
            'position' => $position,
        ]);

        return (new BoardListResource($list))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update the specified list.
     */
    public function update(UpdateBoardListRequest $request, BoardList $list): BoardListResource
    {
        Gate::authorize('update', $list);

        $updated = $this->boardListRepository->update($list, $request->validated());

        return new BoardListResource($updated);
    }

    /**
     * Remove the specified list from storage.
     */
    public function destroy(BoardList $list): JsonResponse
    {
        Gate::authorize('delete', $list);

        $this->boardListRepository->delete($list);

        return response()->json([
            'message' => 'List deleted successfully.',
        ]);
    }

    /**
     * Reorder lists within the specified board.
     */
    public function reorder(ReorderBoardListsRequest $request, Board $board): AnonymousResourceCollection
    {
        Gate::authorize('reorder', [BoardList::class, $board]);

        $this->boardListRepository->reorder((int) $board->id, $request->validated('ordered_ids'));

        $orderedLists = $this->boardListRepository->getForBoard((int) $board->id);

        return BoardListResource::collection($orderedLists);
    }
}

