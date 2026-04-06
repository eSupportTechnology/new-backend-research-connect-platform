<?php

use App\Http\Controllers\FollowerController;
use App\Http\Controllers\InnovationCommentController;
use App\Http\Controllers\InnovationLikeController;
use App\Http\Controllers\SellingItemController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::prefix('upload')->middleware('auth:sanctum')->group(function () {
    Route::post('/research', [UploadController::class, 'uploadResearch']);
    Route::post('/innovation', [UploadController::class, 'uploadInnovation']);
    Route::post('/presigned-url', [UploadController::class, 'getPresignedUrl']);
    Route::post('/selling-image', [SellingItemController::class, 'uploadSellingImage']);
    Route::delete('/file', [UploadController::class, 'deleteFile']);
});
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/my-research', [UploadController::class, 'getMyResearches']);
    Route::get('/my-innovations', [UploadController::class, 'getMyInnovations']);
    Route::put('/innovations/{id}/update-price', [UploadController::class, 'updateInnovationPrice']);
    Route::put('/research/{id}/update-price', [UploadController::class, 'updateResearchPrice']);
});

Route::get('/innovations/top-viewed', [UploadController::class, 'getTopViewedInnovations']);
Route::get('/innovations/{id}', [UploadController::class, 'getInnovationDetails']);

Route::get('/research/top-researches', [UploadController::class, 'getTopViewedResearches']);
Route::get('/research/{id}', [UploadController::class, 'getResearchDetails']);

Route::prefix('research')->group(function () {
    Route::get('/', [UploadController::class, 'getResearches']);
    // ✅ Then wildcard routes
    Route::get('/{id}', [UploadController::class, 'getResearch']);
    Route::get('/{id}/download', [UploadController::class, 'downloadResearch']);
    Route::put('/{id}/status', [UploadController::class, 'updateResearchStatus']);
});

Route::prefix('innovation')->group(function () {
    Route::get('/', [UploadController::class, 'getInnovations']);
    Route::get('/top-viewed', [UploadController::class, 'getTopViewedInnovations']);
    Route::get('/{id}', [UploadController::class, 'getInnovation']);
});
Route::patch('/innovations/{id}/status', [UploadController::class, 'updateInnovationStatus']);
Route::post('/innovations/bulk-status', [UploadController::class, 'bulkUpdateInnovationStatus']);

Route::middleware(['auth:sanctum'])->group(function () {
    // Comments
    Route::get('/innovations/{innovation}/comments', [InnovationCommentController::class, 'index']);
    Route::post('/innovations/{innovation}/comments', [InnovationCommentController::class, 'store']);
    Route::put('/innovations/{innovation}/comments/{comment}', [InnovationCommentController::class, 'update']);
    Route::delete('/innovations/{innovation}/comments/{comment}', [InnovationCommentController::class, 'destroy']);

    // Like/Dislike Comments
    Route::post('/innovations/{innovation}/comments/{comment}/toggle-like', [InnovationCommentController::class, 'toggleLike']);

    // Like/Dislike Innovation
    Route::post('/innovations/{innovation}/toggle-like', [InnovationLikeController::class, 'toggleLike']);

    // Rating stats
    Route::get('/innovations/{innovation}/ratings', [InnovationCommentController::class, 'getAverageRating']);
});
Route::middleware(['auth:sanctum'])->group(function () {
    // Follow/Unfollow
    Route::post('/users/{user}/follow', [FollowerController::class, 'follow']);
    Route::delete('/users/{user}/unfollow', [FollowerController::class, 'unfollow']);
    Route::post('/users/{user}/toggle-follow', [FollowerController::class, 'toggleFollow']);

    // Get followers and following
    Route::get('/users/{user}/followers', [FollowerController::class, 'followers']);
    Route::get('/users/{user}/following', [FollowerController::class, 'following']);

    // Check follow status
    Route::get('/users/{user}/follow-status', [FollowerController::class, 'checkFollowStatus']);
    Route::get('/users/{user}/follow-stats', [FollowerController::class, 'stats']);

    // Suggestions
    Route::get('/follow-suggestions', [FollowerController::class, 'suggestions']);

    // Remove follower
    Route::delete('/followers/{follower}/remove', [FollowerController::class, 'removeFollower']);
});
