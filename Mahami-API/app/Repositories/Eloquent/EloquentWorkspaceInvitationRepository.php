<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Repositories\Contracts\WorkspaceInvitationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EloquentWorkspaceInvitationRepository implements WorkspaceInvitationRepositoryInterface
{
    public function forWorkspace(Workspace $workspace): Collection
    {
        return $workspace->invitations()
            ->whereNull('accepted_at')
            ->whereNull('declined_at')
            ->whereNull('cancelled_at')
            ->where('expires_at', '>', now())
            ->with(['inviter:id,name,email'])
            ->latest()
            ->get();
    }

    public function create(Workspace $workspace, User $inviter, array $data): WorkspaceInvitation
    {
        return WorkspaceInvitation::create([
            'workspace_id' => $workspace->id,
            'email' => strtolower(trim($data['email'])),
            'role' => $data['role'] ?? 'member',
            'token' => Str::random(64),
            'invited_by' => $inviter->id,
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function findByToken(string $token): ?WorkspaceInvitation
    {
        return WorkspaceInvitation::with(['workspace.owner', 'inviter:id,name,email'])
            ->where('token', $token)
            ->first();
    }

    public function accept(WorkspaceInvitation $invitation, User $user): void
    {
        DB::transaction(function () use ($invitation, $user) {
            $invitation->update(['accepted_at' => now()]);

            $invitation->workspace->members()->syncWithoutDetaching([
                $user->id => [
                    'role' => $invitation->role,
                    'status' => 'active',
                ],
            ]);
        });
    }

    public function decline(WorkspaceInvitation $invitation): void
    {
        $invitation->update(['declined_at' => now()]);
    }

    public function cancel(WorkspaceInvitation $invitation): void
    {
        $invitation->update(['cancelled_at' => now()]);
    }

    public function cancelPendingForEmail(Workspace $workspace, string $email): int
    {
        return $workspace->invitations()
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->whereNull('declined_at')
            ->whereNull('cancelled_at')
            ->update(['cancelled_at' => now()]);
    }

    public function pendingForEmail(string $email): Collection
    {
        return WorkspaceInvitation::where('email', strtolower($email))
            ->whereNull('accepted_at')
            ->whereNull('declined_at')
            ->whereNull('cancelled_at')
            ->where('expires_at', '>', now())
            ->with(['workspace', 'inviter'])
            ->get();
    }
}
