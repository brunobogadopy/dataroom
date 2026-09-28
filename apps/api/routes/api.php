<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\NodeController;
use App\Http\Controllers\Api\NodePermissionController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/register',[AuthController::class,'register']);
    Route::post('/auth/login',[AuthController::class,'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/me',[AuthController::class,'me']);
        Route::post('/auth/logout',[AuthController::class,'logout']);

        Route::get('/workspaces',[WorkspaceController::class,'index']);
        Route::post('/workspaces',[WorkspaceController::class,'store']);
        Route::get('/workspaces/{workspace:slug}',[WorkspaceController::class,'show']);

        Route::get('/workspaces/{workspace:slug}/members',[MemberController::class,'index']);
        Route::put('/workspaces/{workspace:slug}/members/{userId}',[MemberController::class,'update']);
        Route::delete('/workspaces/{workspace:slug}/members/{userId}',[MemberController::class,'destroy']);
        Route::get('/workspaces/{workspace:slug}/invitations',[InvitationController::class,'index']);
        Route::post('/workspaces/{workspace:slug}/invitations',[InvitationController::class,'store']);
        Route::delete('/workspaces/{workspace:slug}/invitations/{invitation}',[InvitationController::class,'destroy']);
        Route::post('/invitations/{token}/accept',[InvitationController::class,'accept']);

        Route::get('/workspaces/{workspace:slug}/nodes',[NodeController::class,'index']);
        Route::get('/workspaces/{workspace:slug}/folders/{node}/breadcrumbs',[NodeController::class,'breadcrumbs']);
        Route::post('/workspaces/{workspace:slug}/folders',[NodeController::class,'storeFolder']);
        Route::post('/workspaces/{workspace:slug}/documents',[DocumentController::class,'store']);
        Route::post('/workspaces/{workspace:slug}/files',[FileController::class,'store']);
        Route::get('/workspaces/{workspace:slug}/nodes/{node}/permissions',[NodePermissionController::class,'show']);
        Route::put('/workspaces/{workspace:slug}/nodes/{node}/permissions',[NodePermissionController::class,'update']);

        Route::get('/documents/{node}',[DocumentController::class,'show']);
        Route::put('/documents/{node}',[DocumentController::class,'update']);
        Route::get('/files/{node}',[FileController::class,'show']);
        Route::get('/files/{node}/preview',[FileController::class,'preview']);
        Route::get('/files/{node}/download',[FileController::class,'download']);
        Route::delete('/nodes/{node}',[NodeController::class,'destroy']);

        Route::get('/workspaces/{workspace:slug}/search',SearchController::class);
    });
});
