<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InviteWorkspaceMemberRequest;
use App\Http\Resources\WorkspaceInvitationResource;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Notifications\WorkspaceInvitationNotification;
use App\Notifications\WorkspaceInvitationResponseNotification;
use App\Repositories\Contracts\WorkspaceInvitationRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class WorkspaceInvitationController extends Controller
{
    public function __construct(
        protected WorkspaceInvitationRepositoryInterface $invitationRepository
    ) {}

    /**
     * List active invitations for a workspace.
     */
    public function index(Request $request, Workspace $workspace): AnonymousResourceCollection
    {
        Gate::authorize('manageMembers', $workspace);

        $invitations = $this->invitationRepository->forWorkspace($workspace);

        return WorkspaceInvitationResource::collection($invitations);
    }

    /**
     * Send an invitation to join workspace.
     */
    public function store(InviteWorkspaceMemberRequest $request, Workspace $workspace): JsonResponse
    {
        Gate::authorize('manageMembers', $workspace);

        $email = strtolower(trim($request->validated()['email']));

        // Check if user is already a member
        $existingUser = User::where('email', $email)->first();
        if ($existingUser && $workspace->members()->where('users.id', $existingUser->id)->exists()) {
            return response()->json([
                'message' => 'User is already a member of this workspace',
            ], 422);
        }

        // Cancel previous pending invites for same email
        $workspace->invitations()
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->whereNull('declined_at')
            ->whereNull('cancelled_at')
            ->update(['cancelled_at' => now()]);

        $invitation = $this->invitationRepository->create($workspace, $request->user(), $request->validated());

        // Notify existing registered user
        if ($existingUser) {
            $existingUser->notify(new WorkspaceInvitationNotification($invitation, $workspace, $request->user()));
        }

        return (new WorkspaceInvitationResource($invitation))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Cancel an active invitation.
     */
    public function destroy(Request $request, Workspace $workspace, WorkspaceInvitation $invitation): JsonResponse
    {
        Gate::authorize('manageMembers', $workspace);

        if ($invitation->workspace_id !== $workspace->id) {
            abort(404);
        }

        $this->invitationRepository->cancel($invitation);

        return response()->json([
            'message' => 'Invitation cancelled successfully',
        ], 200);
    }

    /**
     * Get details of an invitation by token (public or auth).
     */
    public function showByToken(string $token): JsonResponse
    {
        $invitation = $this->invitationRepository->findByToken($token);

        if (!$invitation || !$invitation->isValid()) {
            return response()->json([
                'message' => 'This invitation is invalid or has expired',
            ], 404);
        }

        return (new WorkspaceInvitationResource($invitation))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Accept invitation (authenticated user).
     */
    public function accept(Request $request, string $token): JsonResponse
    {
        $invitation = $this->invitationRepository->findByToken($token);

        if (!$invitation || !$invitation->isValid()) {
            return response()->json([
                'message' => 'This invitation is invalid or has expired',
            ], 422);
        }

        $user = $request->user();

        // Add user as member
        $this->invitationRepository->accept($invitation, $user);

        // Mark corresponding notification as read
        $user->notifications()
            ->where('data->type', 'workspace_invitation')
            ->where('data->token', $token)
            ->update(['read_at' => now()]);

        // Notify inviter
        if ($invitation->inviter) {
            $invitation->inviter->notify(new WorkspaceInvitationResponseNotification(
                workspace: $invitation->workspace,
                responder: $user,
                accepted: true
            ));
        }

        return response()->json([
            'message' => 'Invitation accepted successfully',
            'workspace_id' => $invitation->workspace_id,
        ], 200);
    }

    /**
     * Decline invitation.
     */
    public function decline(Request $request, string $token): JsonResponse
    {
        $invitation = $this->invitationRepository->findByToken($token);

        if (!$invitation || !$invitation->isValid()) {
            return response()->json([
                'message' => 'This invitation is invalid or has expired',
            ], 422);
        }

        $this->invitationRepository->decline($invitation);

        $user = $request->user();

        if ($user) {
            $user->notifications()
                ->where('data->type', 'workspace_invitation')
                ->where('data->token', $token)
                ->update(['read_at' => now()]);
        }

        // Notify inviter
        if ($invitation->inviter) {
            $invitation->inviter->notify(new WorkspaceInvitationResponseNotification(
                workspace: $invitation->workspace,
                responder: $user,
                accepted: false,
                declinedEmail: $invitation->email
            ));
        }

        return response()->json([
            'message' => 'Invitation declined successfully',
        ], 200);
    }
}
