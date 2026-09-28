<?php

use App\Http\Controllers\Api\AiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\DocumentHistoryController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\LibraryController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\NodeController;
use App\Http\Controllers\Api\NodePermissionController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\TrashController;
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

        Route::get('/workspaces/{workspace:slug}/favorites',[LibraryController::class,'favorites']);
        Route::get('/workspaces/{workspace:slug}/recent',[LibraryController::class,'recent']);
        Route::get('/workspaces/{workspace:slug}/trash',[LibraryController::class,'trash']);
        Route::get('/workspaces/{workspace:slug}/activity',[LibraryController::class,'activity']);
        Route::post('/nodes/{node}/favorite',[LibraryController::class,'toggleFavorite']);

        Route::get('/nodes/{node}/comments',[CommentController::class,'index']);
        Route::post('/nodes/{node}/comments',[CommentController::class,'store']);
        Route::delete('/comments/{comment}',[CommentController::class,'destroy']);

        Route::get('/notifications',[NotificationController::class,'index']);
        Route::get('/notifications/unread-count',[NotificationController::class,'unreadCount']);
        Route::post('/notifications/{notification}/read',[NotificationController::class,'markRead']);
        Route::post('/notifications/read-all',[NotificationController::class,'markAllRead']);

        Route::get('/workspaces/{workspace:slug}/semantic-search',[AiController::class,'semanticSearch']);
        Route::post('/workspaces/{workspace:slug}/ask',[AiController::class,'ask']);

        Route::get('/documents/{node}',[DocumentController::class,'show']);
        Route::put('/documents/{node}',[DocumentController::class,'update']);
        Route::get('/documents/{node}/versions',[DocumentHistoryController::class,'index']);
        Route::post('/documents/{node}/versions/{revision}/restore',[DocumentHistoryController::class,'restore']);

        Route::get('/files/{node}',[FileController::class,'show']);
        Route::post('/files/{node}/versions',[FileController::class,'newVersion']);
        Route::get('/files/{node}/versions',[FileController::class,'versions']);
        Route::get('/files/{node}/preview',[FileController::class,'preview']);
        Route::get('/files/{node}/download',[FileController::class,'download']);

        Route::delete('/nodes/{node}',[NodeController::class,'destroy']);
        Route::post('/trash/{nodeId}/restore',[TrashController::class,'restore']);
        Route::delete('/trash/{nodeId}',[TrashController::class,'forceDelete']);

        Route::get('/workspaces/{workspace:slug}/search',SearchController::class);
    });
});
