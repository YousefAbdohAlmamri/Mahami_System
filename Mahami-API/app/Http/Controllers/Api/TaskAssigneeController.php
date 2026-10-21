<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TaskAssigneeController extends Controller
{
    public function sync(Request $request, Task $task): JsonResponse
    {
        Gate::authorize('update', $task);

        $validated = $request->validate([
            'user_ids' => ['present', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $task->assignees()->sync($validated['user_ids']);

        return response()->json([
            'message' => 'Task assignees updated successfully.',
            'data' => new TaskResource($task->fresh(['assignees', 'labels', 'checklists.items', 'attachments'])),
        ]);
    }

    public function store(Request $request, Task $task): JsonResponse
    {
        Gate::authorize('update', $task);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $task->assignees()->syncWithoutDetaching([$validated['user_id']]);

        return response()->json([
            'message' => 'Assignee added successfully.',
            'data' => new TaskResource($task->fresh(['assignees', 'labels', 'checklists.items', 'attachments'])),
        ], 201);
    }

    public function destroy(Task $task, User $user): JsonResponse
    {
        Gate::authorize('update', $task);

        $task->assignees()->detach($user->id);

        return response()->json([
            'message' => 'Assignee removed successfully.',
            'data' => new TaskResource($task->fresh(['assignees', 'labels', 'checklists.items', 'attachments'])),
        ]);
    }
}

