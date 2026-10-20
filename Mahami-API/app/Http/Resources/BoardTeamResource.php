<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoardTeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'permission' => $this->pivot?->permission ?? 'editor',
            'members_count' => $this->members()->count(),
            'assigned_at' => $this->pivot?->created_at?->toISOString(),
        ];
    }
}
