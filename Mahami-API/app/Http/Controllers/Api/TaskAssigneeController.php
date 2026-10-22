<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
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

        $previousIds = $task->assignees()->pluck('users.id')->map(fn($id) => (int) $id)->all();
        $requestedIds = array_map('intval', $validated['user_ids']);

        $task->assignees()->sync($requestedIds);
        $newIds = array_diff($requestedIds, $previousIds);
        $currentUserId = (int) $request->user()->id;

        foreach ($newIds as $newId) {
            if ((int) $newId !== $currentUserId) {
                $userToNotify = User::find($newId);
                $userToNotify?->notify(new TaskAssignedNotification($task, $request->user()));
            }
        }

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

        $assigneeId = (int) $validated['user_id'];
        $task->assignees()->syncWithoutDetaching([$assigneeId]);

        if ($assigneeId !== (int) $request->user()->id) {
            $userToNotify = User::find($assigneeId);
            $userToNotify?->notify(new TaskAssignedNotification($task, $request->user()));
        }

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

