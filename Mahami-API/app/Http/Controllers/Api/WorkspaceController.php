<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkspaceRequest;
use App\Http\Requests\TransferWorkspaceOwnershipRequest;
use App\Http\Requests\UpdateWorkspaceRequest;
use App\Http\Resources\WorkspaceResource;
use App\Models\Workspace;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\WorkspaceRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class WorkspaceController extends Controller
{
    public function __construct(
        protected WorkspaceRepositoryInterface $workspaceRepository,
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * List all workspaces the user has access to.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $workspaces = $this->workspaceRepository->forUser($request->user());
        return WorkspaceResource::collection($workspaces);
    }

    /**
     * Create a new workspace.
     */
    public function store(StoreWorkspaceRequest $request): JsonResponse
    {
        $workspace = $this->workspaceRepository->create($request->user(), $request->validated());

        return (new WorkspaceResource($workspace))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show a specific workspace with its members and boards.
     */
    public function show(Request $request, Workspace $workspace): WorkspaceResource
    {
        Gate::authorize('view', $workspace);

        $workspace = $this->workspaceRepository->find($workspace->id);

        return new WorkspaceResource($workspace);
    }

    /**
     * Update workspace details.
     */
    public function update(UpdateWorkspaceRequest $request, Workspace $workspace): WorkspaceResource
    {
        Gate::authorize('update', $workspace);

        $updated = $this->workspaceRepository->update($workspace, $request->validated());

        return new WorkspaceResource($updated);
    }

    /**
     * Delete workspace.
     */
    public function destroy(Request $request, Workspace $workspace): JsonResponse
    {
        Gate::authorize('delete', $workspace);

        $this->workspaceRepository->delete($workspace);

        return response()->json([
            'message' => 'Workspace deleted successfully',
        ], 200);
    }

    /**
     * Transfer workspace ownership.
     */
    public function transferOwnership(TransferWorkspaceOwnershipRequest $request, Workspace $workspace): JsonResponse
    {
        Gate::authorize('transferOwnership', $workspace);

        $newOwner = $this->userRepository->findOrFail((int) $request->validated()['new_owner_id']);

        // Check if new owner is an active member of this workspace
        if (!$this->workspaceRepository->isActiveMember($workspace, (int) $newOwner->id)) {
            return response()->json([
                'message' => 'New owner must be an active member of this workspace',
            ], 422);
        }

        $transferred = $this->workspaceRepository->transferOwnership($workspace, $newOwner);

        return (new WorkspaceResource($transferred))
            ->response()
            ->setStatusCode(200);
    }
}
