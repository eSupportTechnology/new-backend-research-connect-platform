<?php

use App\Http\Controllers\SuperAdminController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('super-admin')->group(function () {
    // Dashboard Stats
    Route::get('/dashboard/stats', [SuperAdminController::class, 'getDashboardStats']);
    
    // Leaderboard Management
    Route::get('/users-by-contribution', [SuperAdminController::class, 'getUsersByContribution']);
    Route::post('/users/{id}/toggle-best', [SuperAdminController::class, 'toggleBestStatus']);
});

// Public featured performers
Route::get('/public/featured-performers', [SuperAdminController::class, 'getFeaturedPerformers']);
