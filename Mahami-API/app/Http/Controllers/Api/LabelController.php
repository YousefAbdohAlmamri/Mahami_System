<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LabelResource;
use App\Models\Board;
use App\Models\Label;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class LabelController extends Controller
{
    public function index(Board $board): AnonymousResourceCollection
    {
        Gate::authorize('view', $board);

        return LabelResource::collection($board->labels()->latest()->get());
    }

    public function store(Request $request, Board $board): JsonResponse
    {
        Gate::authorize('update', $board);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'color' => ['required', 'string', 'max:32'],
        ]);

        $label = $board->labels()->create($validated);

        return response()->json([
            'message' => 'Label created successfully.',
            'data' => new LabelResource($label),
        ], 201);
    }

    public function update(Request $request, Board $board, Label $label): JsonResponse
    {
        Gate::authorize('update', $board);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'color' => ['sometimes', 'required', 'string', 'max:32'],
        ]);

        $label->update($validated);

        return response()->json([
            'message' => 'Label updated successfully.',
            'data' => new LabelResource($label),
        ]);
    }

    public function destroy(Board $board, Label $label): JsonResponse
    {
        Gate::authorize('update', $board);

        $label->delete();

        return response()->json([
            'message' => 'Label deleted successfully.',
        ]);
    }
}

