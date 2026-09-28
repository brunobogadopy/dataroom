<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\NodeController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/workspaces', [WorkspaceController::class, 'index']);
        Route::post('/workspaces', [WorkspaceController::class, 'store']);
        Route::get('/workspaces/{workspace:slug}', [WorkspaceController::class, 'show']);

        Route::get('/workspaces/{workspace:slug}/nodes', [NodeController::class, 'index']);
        Route::post('/workspaces/{workspace:slug}/folders', [NodeController::class, 'storeFolder']);
        Route::post('/workspaces/{workspace:slug}/documents', [DocumentController::class, 'store']);
        Route::get('/documents/{node}', [DocumentController::class, 'show']);
        Route::put('/documents/{node}', [DocumentController::class, 'update']);
        Route::delete('/nodes/{node}', [NodeController::class, 'destroy']);

        Route::get('/workspaces/{workspace:slug}/search', SearchController::class);
    });
});
