<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Notifications\WorkspaceInvitationNotification;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\WorkspaceInvitationRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected WorkspaceInvitationRepositoryInterface $invitationRepository
    ) {}

    /**
     * Handle an incoming registration request.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->userRepository->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
        ]);

        Auth::login($user);

        // Check if there are pending invitations for this newly registered email
        $pendingInvites = $this->invitationRepository->pendingForEmail($user->email);

        foreach ($pendingInvites as $invite) {
            if ($invite->workspace && $invite->inviter) {
                $user->notify(new WorkspaceInvitationNotification($invite, $invite->workspace, $invite->inviter));
            }
        }

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful.',
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        /** @var User $user */
        $user = $request->user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Logged in successfully.',
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    /**
     * Destroy an authenticated session and revoke tokens.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            // Revoke current token if using token auth (skip TransientToken from session)
            $token = $user->currentAccessToken();
            if ($token && method_exists($token, 'delete')) {
                $token->delete();
            }
        }

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Return the currently authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }
}

