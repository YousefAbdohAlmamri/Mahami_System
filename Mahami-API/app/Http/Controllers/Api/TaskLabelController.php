<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Label;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TaskLabelController extends Controller
{
    public function sync(Request $request, Task $task): JsonResponse
    {
        Gate::authorize('update', $task);

        $validated = $request->validate([
            'label_ids' => ['present', 'array'],
            'label_ids.*' => ['integer', 'exists:board_labels,id'],
        ]);

        $task->labels()->sync($validated['label_ids']);

        return response()->json([
            'message' => 'Task labels updated successfully.',
            'data' => new TaskResource($task->fresh(['assignees', 'labels', 'checklists.items', 'attachments'])),
        ]);
    }

    public function attach(Task $task, Label $label): JsonResponse
    {
        Gate::authorize('update', $task);

        $task->labels()->syncWithoutDetaching([$label->id]);

        return response()->json([
            'message' => 'Label attached successfully.',
            'data' => new TaskResource($task->fresh(['assignees', 'labels', 'checklists.items', 'attachments'])),
        ]);
    }

    public function detach(Task $task, Label $label): JsonResponse
    {
        Gate::authorize('update', $task);

        $task->labels()->detach($label->id);

        return response()->json([
            'message' => 'Label detached successfully.',
            'data' => new TaskResource($task->fresh(['assignees', 'labels', 'checklists.items', 'attachments'])),
        ]);
    }
}

