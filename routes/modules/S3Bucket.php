<?php

use App\Http\Controllers\S3Controller;
use Illuminate\Support\Facades\Route;

Route::prefix('s3')->group(function () {
    Route::post('/presigned-url', [S3Controller::class, 'getPresignedUrl']);
    Route::post('/delete-file', [S3Controller::class, 'deleteFile']);
    Route::post('/upload', [S3Controller::class, 'directUpload']);
});
