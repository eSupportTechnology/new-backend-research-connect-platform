<?php

use App\Http\Controllers\FollowerController;
use App\Http\Controllers\InnovationCommentController;
use App\Http\Controllers\VideoUploadPaymentController;
use App\Http\Controllers\InnovationLikeController;
use App\Http\Controllers\ResearchCommentController;
use App\Http\Controllers\ResearchLikeController;
use App\Http\Controllers\SellingItemController;
use App\Http\Controllers\RevisionController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

// Publicly accessible comment and rating routes
Route::get('/innovations/{innovation}/comments', [InnovationCommentController::class, 'index']);
Route::get('/innovations/{innovation}/ratings', [InnovationCommentController::class, 'getAverageRating']);
Route::get('/research/{research}/comments', [ResearchCommentController::class, 'index']);
Route::get('/research/{research}/ratings', [ResearchCommentController::class, 'getAverageRating']);

// Video upload fee config (public)
Route::get('/public/video-upload-config', [VideoUploadPaymentController::class, 'config']);

// Video upload payment (authenticated)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/video-upload-payments/calculate',    [VideoUploadPaymentController::class, 'calculate']);
    Route::post('/video-upload-payments/initiate',     [VideoUploadPaymentController::class, 'initiate']);
    Route::get('/video-upload-payments/{id}/status',   [VideoUploadPaymentController::class, 'status']);
});

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
    Route::get('/my-submissions', [RevisionController::class, 'mySubmissions']);
    Route::post('/innovations/{id}/resubmit', [RevisionController::class, 'resubmitInnovation']);
    Route::post('/research/{id}/resubmit',    [RevisionController::class, 'resubmitResearch']);
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
    Route::post('/bulk-status', [UploadController::class, 'bulkUpdateResearchStatus']);
});

Route::prefix('innovation')->group(function () {
    Route::get('/', [UploadController::class, 'getInnovations']);
    Route::get('/top-viewed', [UploadController::class, 'getTopViewedInnovations']);
    Route::get('/{id}', [UploadController::class, 'getInnovation']);
});

// Adding separately because nested within protected doesn't work for public
Route::post('/innovations/{innovation}/comments', [InnovationCommentController::class, 'store'])->middleware('auth:sanctum');
Route::put('/innovations/{innovation}/comments/{comment}', [InnovationCommentController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/innovations/{innovation}/comments/{comment}', [InnovationCommentController::class, 'destroy'])->middleware('auth:sanctum');
Route::post('/innovations/{innovation}/comments/{comment}/toggle-like', [InnovationCommentController::class, 'toggleLike'])->middleware('auth:sanctum');

Route::post('/research/{research}/comments', [ResearchCommentController::class, 'store'])->middleware('auth:sanctum');
Route::put('/research/{research}/comments/{comment}', [ResearchCommentController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/research/{research}/comments/{comment}', [ResearchCommentController::class, 'destroy'])->middleware('auth:sanctum');
Route::post('/research/{research}/comments/{comment}/toggle-like', [ResearchCommentController::class, 'toggleLike'])->middleware('auth:sanctum');
Route::patch('/innovations/{id}/status', [UploadController::class, 'updateInnovationStatus']);
Route::post('/innovations/bulk-status', [UploadController::class, 'bulkUpdateInnovationStatus']);

// Admin Moderation Routes
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    Route::get('/innovation-comments', [InnovationCommentController::class, 'adminIndex']);
    Route::delete('/innovation-comments/{id}', [InnovationCommentController::class, 'adminDestroy']);
    Route::get('/research-comments', [ResearchCommentController::class, 'adminIndex']);
    Route::delete('/research-comments/{id}', [ResearchCommentController::class, 'adminDestroy']);

    // Content Removal
    Route::delete('/innovations/{id}', [UploadController::class, 'adminDestroyInnovation']);
    Route::delete('/research/{id}', [UploadController::class, 'adminDestroyResearch']);

    // Revision System (Admin)
    Route::post('/innovations/{id}/request-revision',    [RevisionController::class, 'requestInnovationRevision']);
    Route::post('/research/{id}/request-revision',       [RevisionController::class, 'requestResearchRevision']);
    Route::post('/innovations/{id}/permanently-reject',  [RevisionController::class, 'permanentlyRejectInnovation']);
    Route::post('/research/{id}/permanently-reject',     [RevisionController::class, 'permanentlyRejectResearch']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    // Like/Dislike Innovation
    // (POST routes remain protected)
    Route::post('/innovations/{innovation}/toggle-like', [InnovationLikeController::class, 'toggleLike']);

    // Like/Dislike Research
    // (POST routes remain protected)
    Route::post('/research/{research}/toggle-like', [ResearchLikeController::class, 'toggleLike']);
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
