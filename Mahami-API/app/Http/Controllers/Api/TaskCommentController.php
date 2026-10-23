<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskCommentResource;
use App\Models\TaskComment;
use App\Notifications\TaskCommentNotification;
use App\Repositories\Contracts\TaskCommentRepositoryInterface;
use App\Repositories\Contracts\TaskRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TaskCommentController extends Controller
{
    public function __construct(
        protected TaskCommentRepositoryInterface $commentRepository,
        protected TaskRepositoryInterface $taskRepository,
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * Display a listing of comments for a task.
     */
    public function index(Request $request, int $taskId): AnonymousResourceCollection
    {
        $task = $this->taskRepository->findOrFailWithContext($taskId);

        Gate::authorize('viewAny', [TaskComment::class, $task]);

        $comments = $this->commentRepository->forTask($task);

        return TaskCommentResource::collection($comments);
    }

    /**
     * Store a newly created comment in storage.
     */
    public function store(Request $request, int $taskId): JsonResponse
    {
        $task = $this->taskRepository->findOrFailWithContext($taskId);

        Gate::authorize('create', [TaskComment::class, $task]);

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $user = $request->user();

        $comment = $this->commentRepository->create($task, (int) $user->id, $validated['content']);

        // Log activity
        $board = $task->list->board;
        ActivityLogger::log(
            board: $board,
            action: 'commented',
            description: "{$user->name} علق على المهمة: \"{$task->title}\"",
            task: $task,
            user: $user,
            properties: ['comment_id' => $comment->id]
        );

        // Notify mentions and assignees
        preg_match_all('/@([a-zA-Z0-9_\x{0621}-\x{064A}]+)/u', $comment->content, $matches);
        $mentionedNames = array_unique($matches[1] ?? []);

        $notifiedUserIds = [$user->id];

        if (!empty($mentionedNames)) {
            $mentionedUsers = $this->userRepository->findByNames($mentionedNames);
            foreach ($mentionedUsers as $mUser) {
                if ($mUser->id !== $user->id) {
                    $mUser->notify(new TaskCommentNotification($task, $comment, $user, isMention: true));
                    $notifiedUserIds[] = $mUser->id;
                }
            }
        }

        foreach ($this->taskRepository->getAssignees($task) as $assignee) {
            if (!in_array($assignee->id, $notifiedUserIds, true)) {
                $assignee->notify(new TaskCommentNotification($task, $comment, $user, isMention: false));
                $notifiedUserIds[] = $assignee->id;
            }
        }

        return (new TaskCommentResource($this->commentRepository->loadAuthor($comment)))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update the specified comment in storage.
     */
    public function update(Request $request, int $id): TaskCommentResource
    {
        $comment = $this->commentRepository->findOrFailWithContext($id);

        Gate::authorize('update', $comment);

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $comment = $this->commentRepository->update($comment, $validated['content']);

        return new TaskCommentResource($comment);
    }

    /**
     * Remove the specified comment from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $comment = $this->commentRepository->findOrFailWithContext($id);

        Gate::authorize('delete', $comment);

        $this->commentRepository->delete($comment);

        return response()->json([
            'message' => 'Comment deleted successfully',
        ]);
    }
}
