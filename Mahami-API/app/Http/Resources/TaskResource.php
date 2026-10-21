<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Task
 */
class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $allChecklists = $this->relationLoaded('checklists') ? $this->checklists : $this->checklists()->with('items')->get();
        $totalItems = 0;
        $completedItems = 0;
        foreach ($allChecklists as $ch) {
            $items = $ch->relationLoaded('items') ? $ch->items : $ch->items()->get();
            $totalItems += $items->count();
            $completedItems += $items->where('is_completed', true)->count();
        }
        $progressPct = $totalItems > 0 ? (int) round(($completedItems / $totalItems) * 100) : 0;

        return [
            'id' => $this->id,
            'board_list_id' => $this->board_list_id,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'position' => (int) $this->position,
            'assignees' => $this->relationLoaded('assignees')
                ? $this->assignees->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])
                : $this->assignees()->get(['users.id', 'users.name', 'users.email']),
            'labels' => LabelResource::collection($this->relationLoaded('labels') ? $this->labels : $this->labels()->get()),
            'checklists' => ChecklistResource::collection($allChecklists),
            'checklist_summary' => [
                'total' => $totalItems,
                'completed' => $completedItems,
                'percentage' => $progressPct,
            ],
            'attachments' => TaskAttachmentResource::collection($this->relationLoaded('attachments') ? $this->attachments : $this->attachments()->with('uploader')->get()),
            'comments_count' => $this->relationLoaded('comments') ? $this->comments->count() : $this->comments()->count(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
