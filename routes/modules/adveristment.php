<?php

use App\Http\Controllers\AdvertisementController;
use App\Http\Controllers\UserAdvertisementController;
use App\Http\Controllers\PayHereController;
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
    Route::get('/banner', [AdvertisementController::class, 'getBannerAds']);

    // Track engagement
    Route::post('/{id}/impression', [AdvertisementController::class, 'recordImpression']);
    Route::post('/{id}/click', [AdvertisementController::class, 'recordClick']);

    // PayHere Webhook
    Route::post('/payhere/notify', [PayHereController::class, 'notify'])->name('payhere.notify');
});

// User routes - Requires authentication
Route::middleware(['auth:sanctum'])->prefix('user/advertisements')->group(function () {
    Route::get('/', [UserAdvertisementController::class, 'index']);
    Route::post('/', [UserAdvertisementController::class, 'store']);
    Route::get('/{id}/payment-params', [UserAdvertisementController::class, 'getPaymentParams']);
    Route::get('/{id}/verify-payment', [UserAdvertisementController::class, 'verifyPayment']); // NEW
});

// Admin routes - Requires authentication
Route::middleware(['auth:sanctum'])->prefix('admin/advertisements')->group(function () {
    Route::get('/', [AdvertisementController::class, 'adminIndex']); // NEW: Get all ads for admin
    Route::post('/bulk', [AdvertisementController::class, 'bulkAction']); // NEW: Bulk actions
    Route::post('/', [AdvertisementController::class, 'store']);
    Route::put('/{id}', [AdvertisementController::class, 'update']); 
    Route::delete('/{id}', [AdvertisementController::class, 'destroy']);
    Route::get('/analytics', [AdvertisementController::class, 'analytics']);

    // Review actions
    Route::post('/{id}/approve',        [AdvertisementController::class, 'approve']);
    Route::post('/{id}/reject',         [AdvertisementController::class, 'reject']);
    Route::post('/{id}/admin-override', [AdvertisementController::class, 'adminOverride']);
});
