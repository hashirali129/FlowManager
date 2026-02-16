<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\Admin\RequestTypeController;
use App\Http\Controllers\Admin\WorkflowController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [\App\Http\Controllers\UserController::class, 'show']);

    // User Requests
    Route::get('/requests', [RequestController::class, 'index']);
    Route::post('/requests', [RequestController::class, 'store']);
    Route::get('/requests/{id}', [RequestController::class, 'show']);

    // Approvals
    Route::get('/approvals', [ApprovalController::class, 'index']);
    Route::post('/requests/{id}/action', [ApprovalController::class, 'action']);

    // Admin Routes
    Route::apiResource('admin/request-types', RequestTypeController::class);
    Route::apiResource('admin/workflows', WorkflowController::class);

    Route::apiResource('admin/teams', \App\Http\Controllers\Admin\TeamController::class);
    Route::delete('admin/teams/{team}/members/{user}', [\App\Http\Controllers\Admin\TeamController::class, 'removeMember']);
    Route::apiResource('admin/users', \App\Http\Controllers\Admin\UserController::class);
});
