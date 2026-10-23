<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TaskAssigneeController extends Controller
{
    public function __construct(
        protected TaskRepositoryInterface $taskRepository,
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * Sync assignees for a task.
     */
    public function sync(Request $request, Task $task): JsonResponse
    {
        Gate::authorize('update', $task);

        $validated = $request->validate([
            'user_ids' => ['present', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $previousIds = $this->taskRepository->assigneeIds($task);
        $requestedIds = array_map('intval', $validated['user_ids']);

        $this->taskRepository->syncAssignees($task, $requestedIds);

        // Notify only newly added assignees (not the current user)
        $newIds = array_diff($requestedIds, $previousIds);
        $currentUserId = (int) $request->user()->id;

        foreach ($newIds as $newId) {
            if ((int) $newId !== $currentUserId) {
                $userToNotify = $this->userRepository->find((int) $newId);
                $userToNotify?->notify(new TaskAssignedNotification($task, $request->user()));
            }
        }

        return response()->json([
            'message' => 'Task assignees updated successfully.',
            'data' => new TaskResource($this->taskRepository->refreshWithDetails($task)),
        ]);
    }

    /**
     * Add a single assignee to a task.
     */
    public function store(Request $request, Task $task): JsonResponse
    {
        Gate::authorize('update', $task);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $assigneeId = (int) $validated['user_id'];

        $this->taskRepository->addAssignee($task, $assigneeId);

        if ($assigneeId !== (int) $request->user()->id) {
            $userToNotify = $this->userRepository->find($assigneeId);
            $userToNotify?->notify(new TaskAssignedNotification($task, $request->user()));
        }

        return response()->json([
            'message' => 'Assignee added successfully.',
            'data' => new TaskResource($this->taskRepository->refreshWithDetails($task)),
        ], 201);
    }

    /**
     * Remove an assignee from a task.
     */
    public function destroy(Task $task, User $user): JsonResponse
    {
        Gate::authorize('update', $task);

        $this->taskRepository->removeAssignee($task, (int) $user->id);

        return response()->json([
            'message' => 'Assignee removed successfully.',
            'data' => new TaskResource($this->taskRepository->refreshWithDetails($task)),
        ]);
    }
}
