<?php

namespace App\Repositories\Contracts;

use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;

interface TeamRepositoryInterface
{
    /**
     * Get all teams for a given workspace.
     */
    public function getForWorkspace(int $workspaceId): Collection;

    /**
     * Find a team by its ID with relations.
     */
    public function find(int $id): ?Team;

    /**
     * Create a new team.
     */
    public function create(array $data): Team;

    /**
     * Update an existing team.
     */
    public function update(int $id, array $data): Team;

    /**
     * Delete a team.
     */
    public function delete(int $id): bool;

    /**
     * Add a member to a team.
     */
    public function addMember(int $teamId, int $userId, string $role = 'member'): void;

    /**
     * Remove a member from a team.
     */
    public function removeMember(int $teamId, int $userId): void;

    /**
     * Update member's role in a team.
     */
    public function updateMemberRole(int $teamId, int $userId, string $role): void;

    /**
     * Assign a board to a team with a permission level.
     */
    public function assignBoard(int $teamId, int $boardId, string $permission = 'editor'): void;

    /**
     * Remove a board from a team.
     */
    public function removeBoard(int $teamId, int $boardId): void;
}
