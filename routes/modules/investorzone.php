<?php


use App\Http\Controllers\InvestorZoneController;
use Illuminate\Support\Facades\Route;
Route::prefix('investorzone')->group(function () {

    // Public routes
    Route::get('{type}/{category}', [InvestorZoneController::class, 'index']);
    Route::get('{type}/{category}/related', [InvestorZoneController::class, 'related']);
    Route::get('{type}/{category}/project/{id}', [InvestorZoneController::class, 'show']);

    // Auth-protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('posts', [InvestorZoneController::class, 'store']);
        Route::post('project/{id}/like', [InvestorZoneController::class, 'toggleLike']);
    });
});
