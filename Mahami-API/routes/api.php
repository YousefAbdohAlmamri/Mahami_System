<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BoardController;
use App\Http\Controllers\Api\BoardListController;
use App\Http\Controllers\Api\BoardTeamController;
use App\Http\Controllers\Api\LabelController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\TaskAssigneeController;
use App\Http\Controllers\Api\TaskAttachmentController;
use App\Http\Controllers\Api\TaskChecklistController;
use App\Http\Controllers\Api\TaskCommentController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TaskLabelController;
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
Route::post('/register', [AuthController::class, 'register'])->middleware(['guest', 'throttle:auth']);
Route::post('/login', [AuthController::class, 'login'])->middleware(['guest', 'throttle:auth']);

// Public invitation preview by token
Route::get('/invitations/{token}', [WorkspaceInvitationController::class, 'showByToken'])->middleware('throttle:api');

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
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
    Route::post('/workspaces/{workspace}/members/invite', [WorkspaceInvitationController::class, 'store']);
    Route::put('/workspaces/{workspace}/members/{user}', [WorkspaceMemberController::class, 'update'])->whereNumber('user');
    Route::delete('/workspaces/{workspace}/members/{user}', [WorkspaceMemberController::class, 'destroy'])->whereNumber('user');
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

    /*
    |--------------------------------------------------------------------------
    | Phase 3: Labels, Assignees, Checklists & Attachments Routes
    |--------------------------------------------------------------------------
    */
    // Board Labels
    Route::get('/boards/{board}/labels', [LabelController::class, 'index']);
    Route::post('/boards/{board}/labels', [LabelController::class, 'store']);
    Route::put('/boards/{board}/labels/{label}', [LabelController::class, 'update']);
    Route::delete('/boards/{board}/labels/{label}', [LabelController::class, 'destroy']);

    // Task Assignees
    Route::post('/tasks/{task}/assignees', [TaskAssigneeController::class, 'sync']);
    Route::post('/tasks/{task}/assignees/add', [TaskAssigneeController::class, 'store']);
    Route::delete('/tasks/{task}/assignees/{user}', [TaskAssigneeController::class, 'destroy']);

    // Task Labels
    Route::post('/tasks/{task}/labels', [TaskLabelController::class, 'sync']);
    Route::post('/tasks/{task}/labels/{label}', [TaskLabelController::class, 'attach']);
    Route::delete('/tasks/{task}/labels/{label}', [TaskLabelController::class, 'detach']);

    // Checklists & Items
    Route::post('/tasks/{task}/checklists', [TaskChecklistController::class, 'store']);
    Route::put('/checklists/{checklist}', [TaskChecklistController::class, 'update']);
    Route::delete('/checklists/{checklist}', [TaskChecklistController::class, 'destroy']);
    Route::post('/checklists/{checklist}/items', [TaskChecklistController::class, 'addItem']);
    Route::put('/checklist-items/{item}', [TaskChecklistController::class, 'updateItem']);
    Route::delete('/checklist-items/{item}', [TaskChecklistController::class, 'destroyItem']);

    // Attachments
    Route::post('/tasks/{task}/attachments', [TaskAttachmentController::class, 'store']);
    Route::delete('/attachments/{attachment}', [TaskAttachmentController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | Phase 4: Collaboration (Comments, Mentions, Activity Logs) Routes
    |--------------------------------------------------------------------------
    */
    // Task Comments
    Route::get('/tasks/{task}/comments', [TaskCommentController::class, 'index']);
    Route::post('/tasks/{task}/comments', [TaskCommentController::class, 'store']);
    Route::put('/comments/{comment}', [TaskCommentController::class, 'update']);
    Route::delete('/comments/{comment}', [TaskCommentController::class, 'destroy']);

    // Activity Logs
    Route::get('/tasks/{task}/activities', [ActivityLogController::class, 'taskActivities']);
    Route::get('/boards/{board}/activities', [ActivityLogController::class, 'boardActivities']);

    /*
    |--------------------------------------------------------------------------
    | Phase 5: Notifications & Real-Time Alerts Routes
    |--------------------------------------------------------------------------
    */
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | Phase 6: Analytics & Dashboard Reports Routes
    |--------------------------------------------------------------------------
    */
    Route::get('/boards/{board}/analytics', [AnalyticsController::class, 'board']);
    Route::get('/workspaces/{workspace}/analytics', [AnalyticsController::class, 'workspace']);
});
