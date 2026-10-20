<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BoardController;
use App\Http\Controllers\Api\BoardListController;
use App\Http\Controllers\Api\BoardTeamController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\TeamMemberController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Controllers\Api\WorkspaceInvitationController;
use App\Http\Controllers\Api\WorkspaceMemberController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register'])->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');

// Public invitation preview by token
Route::get('/invitations/{token}', [WorkspaceInvitationController::class, 'showByToken']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::get('/me', [AuthController::class, 'me']);

    /*
    |--------------------------------------------------------------------------
    | Workspace Management Routes
    |--------------------------------------------------------------------------
    */
    Route::apiResource('workspaces', WorkspaceController::class);
    Route::post('/workspaces/{workspace}/transfer-ownership', [WorkspaceController::class, 'transferOwnership']);
    Route::get('/workspaces/{workspace}/members', [WorkspaceMemberController::class, 'index']);
    Route::put('/workspaces/{workspace}/members/{user}', [WorkspaceMemberController::class, 'update']);
    Route::delete('/workspaces/{workspace}/members/{user}', [WorkspaceMemberController::class, 'destroy']);
    Route::get('/workspaces/{workspace}/invitations', [WorkspaceInvitationController::class, 'index']);
    Route::post('/workspaces/{workspace}/invitations', [WorkspaceInvitationController::class, 'store']);
    Route::delete('/workspaces/{workspace}/invitations/{invitation}', [WorkspaceInvitationController::class, 'destroy']);

    // Accept / Decline invitation
    Route::post('/invitations/{token}/accept', [WorkspaceInvitationController::class, 'accept']);
    Route::post('/invitations/{token}/decline', [WorkspaceInvitationController::class, 'decline']);

    /*
    |--------------------------------------------------------------------------
    | Team Management Routes
    |--------------------------------------------------------------------------
    */
    Route::get('/workspaces/{workspace}/teams', [TeamController::class, 'index']);
    Route::post('/workspaces/{workspace}/teams', [TeamController::class, 'store']);
    Route::get('/teams/{team}', [TeamController::class, 'show']);
    Route::put('/teams/{team}', [TeamController::class, 'update']);
    Route::delete('/teams/{team}', [TeamController::class, 'destroy']);

    Route::get('/teams/{team}/members', [TeamMemberController::class, 'index']);
    Route::post('/teams/{team}/members', [TeamMemberController::class, 'store']);
    Route::put('/teams/{team}/members/{user}', [TeamMemberController::class, 'update']);
    Route::delete('/teams/{team}/members/{user}', [TeamMemberController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | Board Management Routes
    |--------------------------------------------------------------------------
    */
    Route::apiResource('boards', BoardController::class);

    // Board Teams & Permissions
    Route::get('/boards/{board}/teams', [BoardTeamController::class, 'index']);
    Route::post('/boards/{board}/teams', [BoardTeamController::class, 'store']);
    Route::put('/boards/{board}/teams/{team}', [BoardTeamController::class, 'update']);
    Route::delete('/boards/{board}/teams/{team}', [BoardTeamController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | List / Column Management Routes
    |--------------------------------------------------------------------------
    */
    Route::post('/boards/{board}/lists', [BoardListController::class, 'store']);
    Route::put('/lists/{list}', [BoardListController::class, 'update']);
    Route::delete('/lists/{list}', [BoardListController::class, 'destroy']);
    Route::patch('/boards/{board}/lists/reorder', [BoardListController::class, 'reorder']);

    /*
    |--------------------------------------------------------------------------
    | Task / Card Management Routes
    |--------------------------------------------------------------------------
    */
    Route::post('/lists/{list}/tasks', [TaskController::class, 'store']);
    Route::get('/tasks/{task}', [TaskController::class, 'show']);
    Route::put('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
    Route::patch('/tasks/{task}/move', [TaskController::class, 'move']);
    Route::patch('/lists/{list}/tasks/reorder', [TaskController::class, 'reorder']);
});
