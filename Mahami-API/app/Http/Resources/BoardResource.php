<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Board
 */
class BoardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'workspace_id' => $this->workspace_id,
            'title' => $this->title,
            'description' => $this->description,
            'lists_count' => $this->lists_count !== null
                ? (int) $this->lists_count
                : ($this->relationLoaded('lists') ? $this->lists->count() : 0),
            'labels' => LabelResource::collection($this->whenLoaded('labels')),
            'lists' => BoardListResource::collection($this->whenLoaded('lists')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

