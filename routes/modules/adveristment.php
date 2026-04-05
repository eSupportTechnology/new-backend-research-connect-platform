<?php

use App\Http\Controllers\AdvertisementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Advertisement API Routes
|--------------------------------------------------------------------------
*/

// Public routes - No authentication required
Route::prefix('advertisements')->group(function () {
    // Get ads
    Route::get('/', [AdvertisementController::class, 'index']);
    Route::get('/side', [AdvertisementController::class, 'getSideAds']);
    Route::get('/carousel', [AdvertisementController::class, 'getCarouselAds']);

    // Track engagement
    Route::post('/{id}/impression', [AdvertisementController::class, 'recordImpression']);
    Route::post('/{id}/click', [AdvertisementController::class, 'recordClick']);
});

// Admin routes - Requires authentication
Route::middleware(['auth:sanctum'])->prefix('admin/advertisements')->group(function () {
    Route::get('/', [AdvertisementController::class, 'adminIndex']); // NEW: Get all ads for admin
    Route::post('/', [AdvertisementController::class, 'store']);
    Route::put('/{id}', [AdvertisementController::class, 'update']); // Changed from PUT to POST for _method support
    Route::delete('/{id}', [AdvertisementController::class, 'destroy']);
    Route::get('/analytics', [AdvertisementController::class, 'analytics']);
});
