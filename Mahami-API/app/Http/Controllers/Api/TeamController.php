<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use App\Models\Workspace;
use App\Repositories\Contracts\TeamRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TeamController extends Controller
{
    public function __construct(
        protected TeamRepositoryInterface $teamRepository
    ) {}

    public function index(Workspace $workspace): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', [Team::class, $workspace]);

        $teams = $this->teamRepository->getForWorkspace($workspace->id);
        return TeamResource::collection($teams);
    }

    public function store(StoreTeamRequest $request, Workspace $workspace): JsonResponse
    {
        Gate::authorize('create', [Team::class, $workspace]);

        $team = $this->teamRepository->create([
            'workspace_id' => $workspace->id,
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'created_by' => $request->user()->id,
        ]);

        // Auto-add creator as team lead
        $this->teamRepository->addMember($team->id, $request->user()->id, 'lead');

        return response()->json([
            'message' => 'Team created successfully.',
            'data' => new TeamResource($this->teamRepository->loadRelations($team, ['creator', 'members'])),
        ], 201);
    }

    public function show(Team $team): TeamResource
    {
        Gate::authorize('view', $team);

        return new TeamResource($this->teamRepository->loadRelations($team, ['workspace', 'creator', 'members', 'boards']));
    }

    public function update(UpdateTeamRequest $request, Team $team): JsonResponse
    {
        Gate::authorize('update', $team);

        $updated = $this->teamRepository->update($team->id, $request->validated());

        return response()->json([
            'message' => 'Team updated successfully.',
            'data' => new TeamResource($updated),
        ]);
    }

    public function destroy(Team $team): JsonResponse
    {
        Gate::authorize('delete', $team);

        $this->teamRepository->delete($team->id);

        return response()->json([
            'message' => 'Team deleted successfully.',
        ]);
    }
}
