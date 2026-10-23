<?php

namespace App\Repositories\Eloquent;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Repositories\Contracts\TaskAttachmentRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class EloquentTaskAttachmentRepository implements TaskAttachmentRepositoryInterface
{
    public function store(Task $task, UploadedFile $file, int $userId): TaskAttachment
    {
        $fileName = $file->getClientOriginalName();
        $fileSize = $file->getSize();
        $mimeType = $file->getClientMimeType() ?: 'application/octet-stream';

        $storedPath = $file->store("attachments/{$task->id}", 'public');

        $attachment = $task->attachments()->create([
            'user_id' => $userId,
            'file_name' => $fileName,
            'file_path' => $storedPath,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
        ]);

        return $attachment->load('uploader');
    }

    public function delete(TaskAttachment $attachment): bool
    {
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        return (bool) $attachment->delete();
    }
}
