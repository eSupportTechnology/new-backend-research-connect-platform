<?php

use App\Http\Controllers\InnovationController;
use Illuminate\Support\Facades\Route;

Route::prefix('innovations')->group(function () {
    Route::get('/', [InnovationController::class, 'index']); // Get all innovations
    Route::post('/', [InnovationController::class, 'store']); // Create new innovation
    Route::get('/{id}', [InnovationController::class, 'show']); // Get single innovation
    Route::put('/{id}', [InnovationController::class, 'update']); // Update innovation
    Route::delete('/{id}', [InnovationController::class, 'destroy']); // Delete innovation
});
