<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateWorkspaceMemberRoleRequest;
use App\Http\Resources\WorkspaceMemberResource;
use App\Models\User;
use App\Models\Workspace;
use App\Repositories\Contracts\WorkspaceRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class WorkspaceMemberController extends Controller
{
    public function __construct(
        protected WorkspaceRepositoryInterface $workspaceRepository
    ) {}

    /**
     * List all members of a workspace.
     */
    public function index(Request $request, Workspace $workspace): AnonymousResourceCollection
    {
        Gate::authorize('view', $workspace);

        $members = $this->workspaceRepository->getMembers($workspace);

        return WorkspaceMemberResource::collection($members);
    }

    /**
     * Update a member's role.
     */
    public function update(UpdateWorkspaceMemberRoleRequest $request, Workspace $workspace, User $user): JsonResponse
    {
        Gate::authorize('manageMembers', $workspace);

        // Cannot change owner role directly via role update
        if ($workspace->owner_id === $user->id) {
            return response()->json([
                'message' => 'Cannot modify owner role. Use transfer ownership instead.',
            ], 422);
        }

        $this->workspaceRepository->updateMemberRole($workspace, $user, $request->validated()['role']);

        return response()->json([
            'message' => 'Member role updated successfully',
        ], 200);
    }

    /**
     * Remove a member from the workspace.
     */
    public function destroy(Request $request, Workspace $workspace, User $user): JsonResponse
    {
        Gate::authorize('manageMembers', $workspace);

        if ($workspace->owner_id === $user->id) {
            return response()->json([
                'message' => 'Cannot remove the workspace owner',
            ], 422);
        }

        $this->workspaceRepository->removeMember($workspace, $user);

        return response()->json([
            'message' => 'Member removed successfully',
        ], 200);
    }
}
