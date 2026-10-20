<?php

namespace App\Repositories\Eloquent;

use App\Models\Team;
use App\Repositories\Contracts\TeamRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentTeamRepository implements TeamRepositoryInterface
{
    public function getForWorkspace(int $workspaceId): Collection
    {
        return Team::query()
            ->where('workspace_id', $workspaceId)
            ->withCount('members', 'boards')
            ->with(['creator', 'members'])
            ->latest()
            ->get();
    }

    public function find(int $id): ?Team
    {
        return Team::query()
            ->with(['workspace', 'creator', 'members', 'boards'])
            ->find($id);
    }

    public function create(array $data): Team
    {
        return Team::create($data);
    }

    public function update(int $id, array $data): Team
    {
        $team = Team::findOrFail($id);
        $team->update($data);
        return $team->fresh(['workspace', 'creator', 'members', 'boards']);
    }

    public function delete(int $id): bool
    {
        $team = Team::findOrFail($id);
        return (bool) $team->delete();
    }

    public function addMember(int $teamId, int $userId, string $role = 'member'): void
    {
        $team = Team::findOrFail($teamId);
        $team->members()->syncWithoutDetaching([
            $userId => ['role' => $role],
        ]);
    }

    public function removeMember(int $teamId, int $userId): void
    {
        $team = Team::findOrFail($teamId);
        $team->members()->detach($userId);
    }

    public function updateMemberRole(int $teamId, int $userId, string $role): void
    {
        $team = Team::findOrFail($teamId);
        $team->members()->updateExistingPivot($userId, ['role' => $role]);
    }

    public function assignBoard(int $teamId, int $boardId, string $permission = 'editor'): void
    {
        $team = Team::findOrFail($teamId);
        $team->boards()->syncWithoutDetaching([
            $boardId => ['permission' => $permission],
        ]);
    }

    public function removeBoard(int $teamId, int $boardId): void
    {
        $team = Team::findOrFail($teamId);
        $team->boards()->detach($boardId);
    }
}
