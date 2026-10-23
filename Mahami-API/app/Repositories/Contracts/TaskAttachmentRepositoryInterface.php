<?php

namespace App\Repositories\Contracts;

use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Http\UploadedFile;

interface TaskAttachmentRepositoryInterface
{
    /**
     * Store the uploaded file on the public disk and create its attachment record
     * (uploader relation loaded on the result).
     */
    public function store(Task $task, UploadedFile $file, int $userId): TaskAttachment;

    /**
     * Delete the attachment record and its stored file (if it exists).
     */
    public function delete(TaskAttachment $attachment): bool;
}
