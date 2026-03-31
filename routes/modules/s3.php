<?php

use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::prefix('upload')->middleware('auth:sanctum')->group(function () {
    Route::post('/research', [UploadController::class, 'uploadResearch']);
    Route::post('/innovation', [UploadController::class, 'uploadInnovation']);
    Route::post('/presigned-url', [UploadController::class, 'getPresignedUrl']);
    Route::delete('/file', [UploadController::class, 'deleteFile']);
});

// ✅ FIX: Static routes MUST come before wildcard {id} routes
// Otherwise Laravel matches "top-viewed" and "top-researches" as an {id}

Route::get('/innovations/top-viewed', [UploadController::class, 'getTopViewedInnovations']);
Route::get('/innovations/{id}', [UploadController::class, 'getInnovationDetails']);

Route::get('/research/top-researches', [UploadController::class, 'getTopViewedResearches']);
Route::get('/research/{id}', [UploadController::class, 'getResearchDetails']);

Route::prefix('research')->group(function () {
    Route::get('/', [UploadController::class, 'getResearches']);
    // ✅ Static named routes first
    Route::get('/top-researches', [UploadController::class, 'getTopViewedResearches']);
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
