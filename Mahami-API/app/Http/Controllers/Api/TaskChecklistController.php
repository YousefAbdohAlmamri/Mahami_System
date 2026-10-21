<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChecklistItemResource;
use App\Http\Resources\ChecklistResource;
use App\Models\Task;
use App\Models\TaskChecklist;
use App\Models\TaskChecklistItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TaskChecklistController extends Controller
{
    public function store(Request $request, Task $task): JsonResponse
    {
        Gate::authorize('update', $task);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $maxPos = $task->checklists()->max('position') ?? -1;

        $checklist = $task->checklists()->create([
            'title' => $validated['title'],
            'position' => $maxPos + 1,
        ]);

        return response()->json([
            'message' => 'Checklist created successfully.',
            'data' => new ChecklistResource($checklist->load('items')),
        ], 201);
    }

    public function update(Request $request, TaskChecklist $checklist): JsonResponse
    {
        Gate::authorize('update', $checklist->task);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $checklist->update($validated);

        return response()->json([
            'message' => 'Checklist updated successfully.',
            'data' => new ChecklistResource($checklist->load('items')),
        ]);
    }

    public function destroy(TaskChecklist $checklist): JsonResponse
    {
        Gate::authorize('update', $checklist->task);

        $checklist->delete();

        return response()->json([
            'message' => 'Checklist deleted successfully.',
        ]);
    }

    public function addItem(Request $request, TaskChecklist $checklist): JsonResponse
    {
        Gate::authorize('update', $checklist->task);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $maxPos = $checklist->items()->max('position') ?? -1;

        $item = $checklist->items()->create([
            'title' => $validated['title'],
            'is_completed' => false,
            'position' => $maxPos + 1,
        ]);

        return response()->json([
            'message' => 'Checklist item added successfully.',
            'data' => new ChecklistItemResource($item),
        ], 201);
    }

    public function updateItem(Request $request, TaskChecklistItem $item): JsonResponse
    {
        Gate::authorize('update', $item->checklist->task);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'is_completed' => ['sometimes', 'required', 'boolean'],
        ]);

        $item->update($validated);

        return response()->json([
            'message' => 'Checklist item updated successfully.',
            'data' => new ChecklistItemResource($item),
        ]);
    }

    public function destroyItem(TaskChecklistItem $item): JsonResponse
    {
        Gate::authorize('update', $item->checklist->task);

        $item->delete();

        return response()->json([
            'message' => 'Checklist item deleted successfully.',
        ]);
    }
}

