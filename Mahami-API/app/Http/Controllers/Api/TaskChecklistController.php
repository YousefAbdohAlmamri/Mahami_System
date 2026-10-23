<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChecklistItemResource;
use App\Http\Resources\ChecklistResource;
use App\Models\Task;
use App\Models\TaskChecklist;
use App\Models\TaskChecklistItem;
use App\Repositories\Contracts\TaskChecklistRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TaskChecklistController extends Controller
{
    public function __construct(
        protected TaskChecklistRepositoryInterface $checklistRepository
    ) {}

    /**
     * Create a checklist for a task.
     */
    public function store(Request $request, Task $task): JsonResponse
    {
        Gate::authorize('update', $task);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $checklist = $this->checklistRepository->createChecklist($task, $validated['title']);

        return response()->json([
            'message' => 'Checklist created successfully.',
            'data' => new ChecklistResource($checklist),
        ], 201);
    }

    /**
     * Update a checklist.
     */
    public function update(Request $request, TaskChecklist $checklist): JsonResponse
    {
        Gate::authorize('update', $checklist->task);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $checklist = $this->checklistRepository->updateChecklist($checklist, $validated);

        return response()->json([
            'message' => 'Checklist updated successfully.',
            'data' => new ChecklistResource($checklist),
        ]);
    }

    /**
     * Delete a checklist.
     */
    public function destroy(TaskChecklist $checklist): JsonResponse
    {
        Gate::authorize('update', $checklist->task);

        $this->checklistRepository->deleteChecklist($checklist);

        return response()->json([
            'message' => 'Checklist deleted successfully.',
        ]);
    }

    /**
     * Add an item to a checklist.
     */
    public function addItem(Request $request, TaskChecklist $checklist): JsonResponse
    {
        Gate::authorize('update', $checklist->task);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $item = $this->checklistRepository->addItem($checklist, $validated['title']);

        return response()->json([
            'message' => 'Checklist item added successfully.',
            'data' => new ChecklistItemResource($item),
        ], 201);
    }

    /**
     * Update a checklist item (title and/or completion state).
     */
    public function updateItem(Request $request, TaskChecklistItem $item): JsonResponse
    {
        Gate::authorize('update', $item->checklist->task);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'is_completed' => ['sometimes', 'required', 'boolean'],
        ]);

        $item = $this->checklistRepository->updateItem($item, $validated);

        return response()->json([
            'message' => 'Checklist item updated successfully.',
            'data' => new ChecklistItemResource($item),
        ]);
    }

    /**
     * Delete a checklist item.
     */
    public function destroyItem(TaskChecklistItem $item): JsonResponse
    {
        Gate::authorize('update', $item->checklist->task);

        $this->checklistRepository->deleteItem($item);

        return response()->json([
            'message' => 'Checklist item deleted successfully.',
        ]);
    }
}
