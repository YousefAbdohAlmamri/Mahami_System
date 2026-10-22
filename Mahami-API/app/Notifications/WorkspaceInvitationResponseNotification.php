<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WorkspaceInvitationResponseNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Workspace $workspace,
        public ?User $responder,
        public bool $accepted,
        public ?string $declinedEmail = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $responderName = $this->responder?->name ?? $this->declinedEmail ?? 'مستخدم';
        $statusText = $this->accepted ? 'قبول' : 'رفض';

        return [
            'type' => 'workspace_invitation_response',
            'workspace_id' => $this->workspace->id,
            'workspace_name' => $this->workspace->name,
            'accepted' => $this->accepted,
            'actor' => [
                'id' => $this->responder?->id,
                'name' => $responderName,
            ],
            'message' => "قام {$responderName} بـ{$statusText} دعوتك للانضمام إلى مساحة العمل \"{$this->workspace->name}\"",
            'created_at' => now()->toISOString(),
        ];
    }
}

