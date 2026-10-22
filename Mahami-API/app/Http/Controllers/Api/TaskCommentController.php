<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskCommentResource;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Notifications\TaskCommentNotification;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TaskCommentController extends Controller
{
    /**
     * Display a listing of comments for a task.
     */
    public function index(Request $request, int $taskId): AnonymousResourceCollection
    {
        $task = Task::with('list.board.workspace')->findOrFail($taskId);
        Gate::authorize('viewAny', [TaskComment::class, $task]);

        $comments = $task->comments()->with('user')->oldest()->get();

        return TaskCommentResource::collection($comments);
    }

    /**
     * Store a newly created comment for a task.
     */
    public function store(Request $request, int $taskId): JsonResponse
    {
        $task = Task::with('list.board.workspace')->findOrFail($taskId);
        Gate::authorize('create', [TaskComment::class, $task]);

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $user = $request->user();
        $comment = $task->comments()->create([
            'user_id' => $user->id,
            'content' => $validated['content'],
        ]);

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
            $mentionedUsers = User::whereIn('name', $mentionedNames)->get();
            foreach ($mentionedUsers as $mUser) {
                if ($mUser->id !== $user->id) {
                    $mUser->notify(new TaskCommentNotification($task, $comment, $user, isMention: true));
                    $notifiedUserIds[] = $mUser->id;
                }
            }
        }

        foreach ($task->assignees as $assignee) {
            if (!in_array($assignee->id, $notifiedUserIds, true)) {
                $assignee->notify(new TaskCommentNotification($task, $comment, $user, isMention: false));
                $notifiedUserIds[] = $assignee->id;
            }
        }

        return (new TaskCommentResource($comment->load('user')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update the specified comment.
     */
    public function update(Request $request, int $id): TaskCommentResource
    {
        $comment = TaskComment::with('task.list.board.workspace')->findOrFail($id);
        Gate::authorize('update', $comment);

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $comment->update([
            'content' => $validated['content'],
        ]);

        return new TaskCommentResource($comment->load('user'));
    }

    /**
     * Remove the specified comment.
     */
    public function destroy(int $id): JsonResponse
    {
        $comment = TaskComment::with('task.list.board.workspace')->findOrFail($id);
        Gate::authorize('delete', $comment);

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully',
        ]);
    }
}
