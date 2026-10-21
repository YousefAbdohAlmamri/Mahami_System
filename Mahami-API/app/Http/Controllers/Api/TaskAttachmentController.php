<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskAttachmentResource;
use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class TaskAttachmentController extends Controller
{
    public function store(Request $request, Task $task): JsonResponse
    {
        Gate::authorize('update', $task);

        $request->validate([
            'file' => ['required', 'file', 'max:10240'], // 10MB limit
        ]);

        $uploadedFile = $request->file('file');
        $fileName = $uploadedFile->getClientOriginalName();
        $fileSize = $uploadedFile->getSize();
        $mimeType = $uploadedFile->getClientMimeType() ?: 'application/octet-stream';

        $storedPath = $uploadedFile->store("attachments/{$task->id}", 'public');

        $attachment = $task->attachments()->create([
            'user_id' => $request->user()->id,
            'file_name' => $fileName,
            'file_path' => $storedPath,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
        ]);

        return response()->json([
            'message' => 'Attachment uploaded successfully.',
            'data' => new TaskAttachmentResource($attachment->load('uploader')),
        ], 201);
    }

    public function destroy(TaskAttachment $attachment): JsonResponse
    {
        Gate::authorize('update', $attachment->task);

        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();

        return response()->json([
            'message' => 'Attachment deleted successfully.',
        ]);
    }
}

