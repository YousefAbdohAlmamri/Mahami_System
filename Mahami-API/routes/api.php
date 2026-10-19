<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BoardController;
use App\Http\Controllers\Api\BoardListController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register'])->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::get('/me', [AuthController::class, 'me']);

    /*
    |--------------------------------------------------------------------------
    | Board Management Routes
    |--------------------------------------------------------------------------
    */
    Route::apiResource('boards', BoardController::class);

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
