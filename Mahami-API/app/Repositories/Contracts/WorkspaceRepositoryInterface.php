<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Collection;

interface WorkspaceRepositoryInterface
{
    /**
     * Get all workspaces the user is a member of.
     */
    public function forUser(User $user): Collection;

    /**
     * Find a workspace with its members.
     */
    public function find(int $id): ?Workspace;

    /**
     * Create a new workspace and assign user as owner in transaction.
     */
    public function create(User $user, array $data): Workspace;

    /**
     * Update workspace details.
     */
    public function update(Workspace $workspace, array $data): Workspace;

    /**
     * Delete workspace and cascade.
     */
    public function delete(Workspace $workspace): bool;

    /**
     * Transfer workspace ownership to another active member.
     */
    public function transferOwnership(Workspace $workspace, User $newOwner): Workspace;

    /**
     * Add member to workspace.
     */
    public function addMember(Workspace $workspace, User $user, string $role = 'member'): void;

    /**
     * Update member's role.
     */
    public function updateMemberRole(Workspace $workspace, User $user, string $newRole): void;

    /**
     * Remove member from workspace.
     */
    public function removeMember(Workspace $workspace, User $user): void;
}

