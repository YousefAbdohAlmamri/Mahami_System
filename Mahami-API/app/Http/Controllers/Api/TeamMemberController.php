<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddTeamMemberRequest;
use App\Http\Resources\TeamMemberResource;
use App\Models\Team;
use App\Models\User;
use App\Repositories\Contracts\TeamRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TeamMemberController extends Controller
{
    public function __construct(
        protected TeamRepositoryInterface $teamRepository
    ) {}

    public function index(Team $team): AnonymousResourceCollection
    {
        Gate::authorize('view', $team);

        return TeamMemberResource::collection($team->members);
    }

    public function store(AddTeamMemberRequest $request, Team $team): JsonResponse
    {
        Gate::authorize('manageMembers', $team);

        $userId = (int) $request->validated('user_id');
        $targetUser = User::findOrFail($userId);

        // Verify target user is a member/owner of the workspace
        $workspace = $team->workspace;
        $isWorkspaceMember = $workspace->owner_id === $targetUser->id ||
            $workspace->members()->where('users.id', $targetUser->id)->where('status', 'active')->exists();

        if (!$isWorkspaceMember) {
            return response()->json([
                'message' => 'User must be a member of the workspace before being added to a team.',
            ], 422);
        }

        $role = $request->validated('role', 'member');
        $this->teamRepository->addMember($team->id, $userId, $role);

        $updatedMember = $team->members()->where('users.id', $userId)->first();

        return response()->json([
            'message' => 'Member added to team successfully.',
            'data' => new TeamMemberResource($updatedMember),
        ], 201);
    }

    public function update(Request $request, Team $team, User $user): JsonResponse
    {
        Gate::authorize('manageMembers', $team);

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:lead,member'],
        ]);

        if (!$team->hasMember($user)) {
            return response()->json([
                'message' => 'User is not a member of this team.',
            ], 404);
        }

        $this->teamRepository->updateMemberRole($team->id, $user->id, $validated['role']);
        $updatedMember = $team->members()->where('users.id', $user->id)->first();

        return response()->json([
            'message' => 'Team member role updated successfully.',
            'data' => new TeamMemberResource($updatedMember),
        ]);
    }

    public function destroy(Team $team, User $user): JsonResponse
    {
        Gate::authorize('manageMembers', $team);

        if (!$team->hasMember($user)) {
            return response()->json([
                'message' => 'User is not a member of this team.',
            ], 404);
        }

        $this->teamRepository->removeMember($team->id, $user->id);

        return response()->json([
            'message' => 'Member removed from team successfully.',
        ]);
    }
}
