<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChecklistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = $this->items ?? collect();
        $totalItems = $items->count();
        $completedItems = $items->where('is_completed', true)->count();
        $progress = $totalItems > 0 ? (int) round(($completedItems / $totalItems) * 100) : 0;

        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'title' => $this->title,
            'position' => $this->position,
            'total_items' => $totalItems,
            'completed_items' => $completedItems,
            'progress_percentage' => $progress,
            'items' => ChecklistItemResource::collection($items),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

