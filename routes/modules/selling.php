<?php


use App\Http\Controllers\SellingItemController;
use Illuminate\Support\Facades\Route;


Route::prefix('selling')->group(function () {
    Route::get('/', [SellingItemController::class, 'index']);
    Route::get('/{id}', [SellingItemController::class, 'show']);
});
// Add these routes to your existing routes file
Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('selling')->group(function () {
        Route::post('/add', [SellingItemController::class, 'addToSelling']);
        Route::get('/my-items', [SellingItemController::class, 'getMySellingItems']);
        Route::get('/stats', [SellingItemController::class, 'getSellingStats']);
        Route::get('/manage/{id}', [SellingItemController::class, 'getSellingItem']);
        Route::put('/{id}', [SellingItemController::class, 'updateSellingItem']);
        Route::delete('/{id}', [SellingItemController::class, 'removeFromSelling']);
        Route::post('/{id}/initiate-purchase', [SellingItemController::class, 'initiatePurchase']);
        Route::get('/orders/my-purchases', [SellingItemController::class, 'getMyPurchases']);
        Route::get('/orders/my-sales', [SellingItemController::class, 'getMySales']);
        Route::post('/{id}/purchase', [SellingItemController::class, 'trackPurchase']);
        Route::post('/{id}/view', [SellingItemController::class, 'trackView']);
    });
});
