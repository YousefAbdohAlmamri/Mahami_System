<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskAttachmentResource;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Repositories\Contracts\TaskAttachmentRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TaskAttachmentController extends Controller
{
    public function __construct(
        protected TaskAttachmentRepositoryInterface $attachmentRepository
    ) {}

    /**
     * Upload an attachment to a task.
     */
    public function store(Request $request, Task $task): JsonResponse
    {
        Gate::authorize('update', $task);

        $request->validate([
            'file' => ['required', 'file', 'max:10240'], // 10MB limit
        ]);

        $attachment = $this->attachmentRepository->store(
            $task,
            $request->file('file'),
            (int) $request->user()->id
        );

        return response()->json([
            'message' => 'Attachment uploaded successfully.',
            'data' => new TaskAttachmentResource($attachment),
        ], 201);
    }

    /**
     * Delete an attachment.
     */
    public function destroy(TaskAttachment $attachment): JsonResponse
    {
        Gate::authorize('update', $attachment->task);

        $this->attachmentRepository->delete($attachment);

        return response()->json([
            'message' => 'Attachment deleted successfully.',
        ]);
    }
}
