<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkspaceInvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'workspace_name' => $this->workspace?->name,
            'email' => $this->email,
            'role' => $this->role,
            'token' => $this->token,
            'inviter' => [
                'id' => $this->inviter?->id,
                'name' => $this->inviter?->name,
                'email' => $this->inviter?->email,
            ],
            'expires_at' => $this->expires_at?->toIso8601String(),
            'is_valid' => $this->isValid(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

