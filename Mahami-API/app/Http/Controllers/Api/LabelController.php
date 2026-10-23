<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LabelResource;
use App\Models\Board;
use App\Models\Label;
use App\Repositories\Contracts\LabelRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class LabelController extends Controller
{
    public function __construct(
        protected LabelRepositoryInterface $labelRepository
    ) {}

    /**
     * Get all labels for a board.
     */
    public function index(Board $board): AnonymousResourceCollection
    {
        Gate::authorize('view', $board);

        return LabelResource::collection($this->labelRepository->forBoard($board));
    }

    /**
     * Create a new label for a board.
     */
    public function store(Request $request, Board $board): JsonResponse
    {
        Gate::authorize('update', $board);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'color' => ['required', 'string', 'max:32'],
        ]);

        $label = $this->labelRepository->create($board, $validated);

        return response()->json([
            'message' => 'Label created successfully.',
            'data' => new LabelResource($label),
        ], 201);
    }

    /**
     * Update a label.
     */
    public function update(Request $request, Board $board, Label $label): JsonResponse
    {
        Gate::authorize('update', $board);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'color' => ['sometimes', 'required', 'string', 'max:32'],
        ]);

        $label = $this->labelRepository->update($label, $validated);

        return response()->json([
            'message' => 'Label updated successfully.',
            'data' => new LabelResource($label),
        ]);
    }

    /**
     * Delete a label.
     */
    public function destroy(Board $board, Label $label): JsonResponse
    {
        Gate::authorize('update', $board);

        $this->labelRepository->delete($label);

        return response()->json([
            'message' => 'Label deleted successfully.',
        ]);
    }
}
