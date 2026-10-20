<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currentUserId = $request->user()?->id;

        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'name' => $this->name,
            'description' => $this->description,
            'created_by' => $this->created_by,
            'creator_name' => $this->creator?->name,
            'members_count' => $this->members_count ?? $this->members()->count(),
            'boards_count' => $this->boards_count ?? $this->boards()->count(),
            'current_user_role' => $currentUserId ? $this->getMemberRole($currentUserId) : null,
            'members' => TeamMemberResource::collection($this->whenLoaded('members')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
