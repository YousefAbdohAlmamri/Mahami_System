<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Database\Eloquent\Collection;

interface WorkspaceInvitationRepositoryInterface
{
    /**
     * Get pending invitations for a workspace.
     */
    public function forWorkspace(Workspace $workspace): Collection;

    /**
     * Create an invitation with secure 64-char token.
     */
    public function create(Workspace $workspace, User $inviter, array $data): WorkspaceInvitation;

    /**
     * Find invitation by token.
     */
    public function findByToken(string $token): ?WorkspaceInvitation;

    /**
     * Accept invitation and add user as workspace member.
     */
    public function accept(WorkspaceInvitation $invitation, User $user): void;

    /**
     * Decline invitation.
     */
    public function decline(WorkspaceInvitation $invitation): void;

    /**
     * Cancel invitation.
     */
    public function cancel(WorkspaceInvitation $invitation): void;

    /**
     * Cancel every still-pending invitation of an email in the workspace.
     */
    public function cancelPendingForEmail(Workspace $workspace, string $email): int;

    /**
     * Still-valid pending invitations for an email (workspace + inviter loaded).
     */
    public function pendingForEmail(string $email): Collection;
}
