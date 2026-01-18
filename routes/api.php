<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\DevlogController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/devlog/posts', [DevlogController::class, 'index']);
Route::post('/devlog/posts', [DevlogController::class, 'store'])->middleware('auth:sanctum');
Route::patch('/devlog/{id}', [DevlogController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/devlog/{id}', [DevlogController::class, 'destroy'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('projects')->group(function () {
        Route::get('/', [ProjectController::class, 'index']);
        Route::post('/', [ProjectController::class, 'store']);
        Route::post('/join', [ProjectController::class, 'join']);
        Route::get('/{id}', [ProjectController::class, 'show']);
        Route::put('/{id}', [ProjectController::class, 'update']);
        Route::delete('/{id}', [ProjectController::class, 'destroy']);
        Route::get('/{id}/members', [ProjectController::class, 'members']);
        Route::delete('/{projectId}/members/{userId}', [ProjectController::class, 'removeMember']);
        Route::get('/{id}/tasks', [TaskController::class, 'index']);
        Route::post('/{id}/tasks', [TaskController::class, 'store']);
        Route::put('/{projectId}/members/{userId}/role', [ProjectController::class, 'updateMemberRole']);
    });

    Route::prefix('tasks')->group(function () {
        Route::get('/{id}', [TaskController::class, 'show']);
        Route::put('/{id}', [TaskController::class, 'update']);
        Route::delete('/{id}', [TaskController::class, 'destroy']);
    });
});
