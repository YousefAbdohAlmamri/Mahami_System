<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Label;
use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TaskLabelController extends Controller
{
    public function __construct(
        protected TaskRepositoryInterface $taskRepository
    ) {}

    /**
     * Sync labels for a task.
     */
    public function sync(Request $request, Task $task): JsonResponse
    {
        Gate::authorize('update', $task);

        $validated = $request->validate([
            'label_ids' => ['present', 'array'],
            'label_ids.*' => ['integer', 'exists:board_labels,id'],
        ]);

        $this->taskRepository->syncLabels($task, $validated['label_ids']);

        return response()->json([
            'message' => 'Task labels updated successfully.',
            'data' => new TaskResource($this->taskRepository->refreshWithDetails($task)),
        ]);
    }

    /**
     * Attach a single label to a task.
     */
    public function attach(Task $task, Label $label): JsonResponse
    {
        Gate::authorize('update', $task);

        $this->taskRepository->attachLabel($task, (int) $label->id);

        return response()->json([
            'message' => 'Label attached successfully.',
            'data' => new TaskResource($this->taskRepository->refreshWithDetails($task)),
        ]);
    }

    /**
     * Detach a label from a task.
     */
    public function detach(Task $task, Label $label): JsonResponse
    {
        Gate::authorize('update', $task);

        $this->taskRepository->detachLabel($task, (int) $label->id);

        return response()->json([
            'message' => 'Label detached successfully.',
            'data' => new TaskResource($this->taskRepository->refreshWithDetails($task)),
        ]);
    }
}
