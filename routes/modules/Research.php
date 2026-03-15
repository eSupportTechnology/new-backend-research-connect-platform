<?php


use App\Http\Controllers\ResearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('research')->group(function () {
    Route::get('/', [ResearchController::class, 'index']); // Get all research
    Route::post('/', [ResearchController::class, 'store']); // Create new research
    Route::get('/{id}', [ResearchController::class, 'show']); // Get single research
    Route::put('/{id}', [ResearchController::class, 'update']); // Update research
    Route::delete('/{id}', [ResearchController::class, 'destroy']); // Delete research
    Route::post('/{id}/download', [ResearchController::class, 'incrementDownloads']); // Track downloads
});
