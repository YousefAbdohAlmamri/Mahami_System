<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\TaskComment
 */
class TaskCommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentUser = $request->user();

        // Extract mentions (@username or @name)
        preg_match_all('/@([a-zA-Z0-9_\x{0621}-\x{064A}]+)/u', $this->content, $matches);
        $mentions = array_unique($matches[1] ?? []);

        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'content' => $this->content,
            'mentions' => array_values($mentions),
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
            ],
            'can_edit' => $currentUser && $currentUser->id === $this->user_id,
            'can_delete' => $currentUser && (
                $currentUser->id === $this->user_id ||
                ($this->task?->list?->board?->user_id === $currentUser->id)
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

