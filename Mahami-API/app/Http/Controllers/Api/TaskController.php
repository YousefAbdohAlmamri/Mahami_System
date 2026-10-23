<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\MoveTaskRequest;
use App\Http\Requests\Tasks\ReorderTasksRequest;
use App\Http\Requests\Tasks\StoreTaskRequest;
use App\Http\Requests\Tasks\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\BoardList;
use App\Models\Task;
use App\Repositories\Contracts\BoardListRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    public function __construct(
        protected TaskRepositoryInterface $taskRepository,
        protected BoardListRepositoryInterface $boardListRepository
    ) {}

    /**
     * Store a newly created task in a list.
     */
    public function store(StoreTaskRequest $request, BoardList $list): JsonResponse
    {
        Gate::authorize('create', [Task::class, $list]);

        $position = $this->taskRepository->getNextPosition((int) $list->id);

        $task = $this->taskRepository->create([
            'board_list_id' => $list->id,
            'title' => $request->validated('title'),
            'description' => $request->validated('description'),
            'priority' => $request->validated('priority', 'medium'),
            'due_date' => $request->validated('due_date'),
            'position' => $position,
        ]);

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified task details.
     */
    public function show(Task $task): TaskResource
    {
        Gate::authorize('view', $task);

        return new TaskResource($task);
    }

    /**
     * Update the specified task.
     */
    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        Gate::authorize('update', $task);

        $updated = $this->taskRepository->update($task, $request->validated());

        return new TaskResource($updated);
    }

    /**
     * Remove the specified task from storage.
     */
    public function destroy(Task $task): JsonResponse
    {
        Gate::authorize('delete', $task);

        $this->taskRepository->delete($task);

        return response()->json([
            'message' => 'Task deleted successfully.',
        ]);
    }

    /**
     * Move a task between columns or to a new position.
     */
    public function move(MoveTaskRequest $request, Task $task): TaskResource
    {
        $targetList = $this->boardListRepository->findOrFail((int) $request->validated('target_list_id'));

        Gate::authorize('move', [$task, $targetList]);

        $moved = $this->taskRepository->move(
            $task,
            (int) $request->validated('target_list_id'),
            (int) $request->validated('position')
        );

        return new TaskResource($moved);
    }

    /**
     * Reorder tasks within a specific column.
     */
    public function reorder(ReorderTasksRequest $request, BoardList $list): AnonymousResourceCollection
    {
        Gate::authorize('reorder', [Task::class, $list]);

        $this->taskRepository->reorder((int) $list->id, $request->validated('ordered_ids'));

        $orderedTasks = $this->taskRepository->getForList((int) $list->id);

        return TaskResource::collection($orderedTasks);
    }
}

