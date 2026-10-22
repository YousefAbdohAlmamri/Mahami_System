<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WorkspaceInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public WorkspaceInvitation $invitation,
        public Workspace $workspace,
        public User $inviter
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $roleName = match ($this->invitation->role) {
            'admin' => 'مدير',
            'guest' => 'ضيف',
            default => 'عضو',
        };

        return [
            'type' => 'workspace_invitation',
            'workspace_id' => $this->workspace->id,
            'workspace_name' => $this->workspace->name,
            'invitation_id' => $this->invitation->id,
            'token' => $this->invitation->token,
            'role' => $this->invitation->role,
            'actor' => [
                'id' => $this->inviter->id,
                'name' => $this->inviter->name,
                'email' => $this->inviter->email,
            ],
            'message' => "تمت دعوتك للانضمام إلى مساحة العمل \"{$this->workspace->name}\" كـ {$roleName} بواسطة {$this->inviter->name}",
            'created_at' => now()->toISOString(),
        ];
    }
}

