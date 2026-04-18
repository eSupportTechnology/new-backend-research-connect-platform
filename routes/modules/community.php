<?php

use App\Http\Controllers\Community\CommunityController;
use Illuminate\Support\Facades\Route;

Route::prefix('community')->group(function () {
    // Public routes
    Route::get('/posts', [CommunityController::class, 'index']);
    Route::get('/posts/{id}', [CommunityController::class, 'show']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/posts', [CommunityController::class, 'store']);
        Route::post('/posts/{id}/like', [CommunityController::class, 'toggleLike']);
        Route::post('/posts/{id}/comment', [CommunityController::class, 'storeComment']);
    });
});
