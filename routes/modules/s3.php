<?php

use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;


Route::prefix('upload')->middleware('auth:sanctum')->group(function () {
    Route::post('/research', [UploadController::class, 'uploadResearch']);
    Route::post('/innovation', [UploadController::class, 'uploadInnovation']);
    Route::post('/presigned-url', [UploadController::class, 'getPresignedUrl']);
    Route::delete('/file', [UploadController::class, 'deleteFile']);
});

Route::prefix('research')->group(function () {
    Route::get('/', [UploadController::class, 'getResearches']);
    Route::get('/{id}', [UploadController::class, 'getResearch']);
    Route::get('/{id}/download', [UploadController::class, 'downloadResearch']);
    Route::put('/{id}/status', [UploadController::class, 'updateResearchStatus']);
});

Route::prefix('innovation')->group(function () {
    Route::get('/', [UploadController::class, 'getInnovations']);
    Route::get('/{id}', [UploadController::class, 'getInnovation']);
});
