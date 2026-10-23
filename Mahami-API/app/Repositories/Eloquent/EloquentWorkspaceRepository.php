<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Models\Workspace;
use App\Repositories\Contracts\WorkspaceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EloquentWorkspaceRepository implements WorkspaceRepositoryInterface
{
    public function forUser(User $user): Collection
    {
        return $user->workspaces()
            ->withCount(['members', 'boards'])
            ->with(['owner:id,name,email'])
            ->orderBy('workspaces.name')
            ->get();
    }

    public function find(int $id): ?Workspace
    {
        return Workspace::with([
            'owner:id,name,email',
            'members' => function ($q) {
                $q->select('users.id', 'users.name', 'users.email')
                  ->withPivot('role', 'status', 'created_at');
            },
            'boards' => function ($q) {
                $q->withCount('lists')->latest();
            },
        ])->find($id);
    }

    public function create(User $user, array $data): Workspace
    {
        return DB::transaction(function () use ($user, $data) {
            $baseSlug = Str::slug($data['name']);
            $slug = $baseSlug . '-' . Str::lower(Str::random(6));

            $workspace = Workspace::create([
                'owner_id' => $user->id,
                'name' => $data['name'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'is_archived' => false,
            ]);

            // Add creator as owner member
            $workspace->members()->attach($user->id, [
                'role' => 'owner',
                'status' => 'active',
            ]);

            return $workspace->load(['owner', 'members']);
        });
    }

    public function update(Workspace $workspace, array $data): Workspace
    {
        if (isset($data['name']) && $data['name'] !== $workspace->name) {
            $data['slug'] = Str::slug($data['name']) . '-' . Str::lower(Str::random(6));
        }

        $workspace->update($data);
        return $workspace->fresh(['owner', 'members']);
    }

    public function delete(Workspace $workspace): bool
    {
        return DB::transaction(function () use ($workspace) {
            return (bool) $workspace->delete();
        });
    }

    public function transferOwnership(Workspace $workspace, User $newOwner): Workspace
    {
        return DB::transaction(function () use ($workspace, $newOwner) {
            $previousOwnerId = $workspace->owner_id;

            $workspace->update(['owner_id' => $newOwner->id]);

            // Demote old owner to admin
            $workspace->members()->updateExistingPivot($previousOwnerId, [
                'role' => 'admin',
            ]);

            // Promote new owner to owner role
            $workspace->members()->syncWithoutDetaching([
                $newOwner->id => ['role' => 'owner', 'status' => 'active'],
            ]);

            return $workspace->fresh(['owner', 'members']);
        });
    }

    public function addMember(Workspace $workspace, User $user, string $role = 'member'): void
    {
        $workspace->members()->syncWithoutDetaching([
            $user->id => [
                'role' => $role,
                'status' => 'active',
            ],
        ]);
    }

    public function updateMemberRole(Workspace $workspace, User $user, string $newRole): void
    {
        $workspace->members()->updateExistingPivot($user->id, [
            'role' => $newRole,
        ]);
    }

    public function removeMember(Workspace $workspace, User $user): void
    {
        $workspace->members()->detach($user->id);
    }

    public function findOrFail(int $id): Workspace
    {
        return Workspace::findOrFail($id);
    }

    public function getMembers(Workspace $workspace): Collection
    {
        return $workspace->members()
            ->select('users.id', 'users.name', 'users.email')
            ->withPivot('role', 'status', 'created_at')
            ->orderBy('workspace_members.role')
            ->get();
    }

    public function countMembers(Workspace $workspace): int
    {
        return $workspace->members()->count();
    }

    public function hasMember(Workspace $workspace, int $userId): bool
    {
        return $workspace->members()->where('users.id', $userId)->exists();
    }

    public function isActiveMember(Workspace $workspace, int $userId): bool
    {
        return $workspace->members()
            ->where('users.id', $userId)
            ->where('status', 'active')
            ->exists();
    }

    public function isOwnerOrActiveMember(Workspace $workspace, int $userId): bool
    {
        return (int) $workspace->owner_id === $userId
            || $this->isActiveMember($workspace, $userId);
    }

    public function getBoardsForAnalytics(Workspace $workspace): Collection
    {
        return $workspace->boards()->with(['lists.tasks.assignees'])->get();
    }
}
