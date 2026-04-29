<?php

use App\Http\Controllers\SellingItemController;
use Illuminate\Support\Facades\Route;

Route::prefix('selling')->group(function () {

    // ── Public static paths — must come before /{id} wildcard ────────────────
    Route::get('/',                          [SellingItemController::class, 'index']);
    Route::get('/seller/{userId}/products',  [SellingItemController::class, 'getSellerProducts']);

    // ── Protected routes ──────────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/add',                  [SellingItemController::class, 'addToSelling']);
        Route::post('/add-product',          [SellingItemController::class, 'addDirectProduct']);
        Route::get('/my-products',           [SellingItemController::class, 'getMyProducts']);
        Route::get('/my-items',              [SellingItemController::class, 'getMySellingItems']);
        Route::get('/stats',                 [SellingItemController::class, 'getSellingStats']);
        Route::get('/manage/{id}',           [SellingItemController::class, 'getSellingItem']);
        Route::get('/orders/my-purchases',   [SellingItemController::class, 'getMyPurchases']);
        Route::get('/orders/my-sales',       [SellingItemController::class, 'getMySales']);
        Route::delete('/orders/{id}',        [SellingItemController::class, 'cancelOrder']);
        Route::put('/orders/{id}/courier',   [SellingItemController::class, 'updateCourierDetails']);
        Route::put('/orders/{id}/delivery-status', [SellingItemController::class, 'updateDeliveryStatus']);
        Route::post('/{id}/initiate-purchase',[SellingItemController::class, 'initiatePurchase']);
        Route::post('/{id}/place-cod-order', [SellingItemController::class, 'placeCodOrder']);
        Route::post('/{id}/purchase',        [SellingItemController::class, 'trackPurchase']);
        Route::post('/{id}/view',            [SellingItemController::class, 'trackView']);
        Route::put('/{id}',                  [SellingItemController::class, 'updateSellingItem']);
        Route::delete('/{id}',               [SellingItemController::class, 'removeFromSelling']);
    });

    // ── Public wildcard — must be last ────────────────────────────────────────
    Route::get('/{id}',                      [SellingItemController::class, 'show']);
});