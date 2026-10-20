<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkspaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currentUserId = $request->user()?->id;
        $currentUserMember = $this->members?->firstWhere('id', $currentUserId);
        $currentUserRole = $this->owner_id === $currentUserId
            ? 'owner'
            : ($currentUserMember?->pivot->role ?? 'member');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_archived' => (bool) $this->is_archived,
            'owner' => [
                'id' => $this->owner?->id,
                'name' => $this->owner?->name,
                'email' => $this->owner?->email,
            ],
            'current_user_role' => $currentUserRole,
            'members_count' => $this->members_count ?? $this->members?->count() ?? 1,
            'boards_count' => $this->boards_count ?? $this->boards?->count() ?? 0,
            'members' => WorkspaceMemberResource::collection($this->whenLoaded('members')),
            'boards' => BoardResource::collection($this->whenLoaded('boards')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

