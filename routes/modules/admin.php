<?php

use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\Cms\HubCardController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('super-admin')->group(function () {
    // Dashboard Stats
    Route::get('/dashboard/stats', [SuperAdminController::class, 'getDashboardStats']);
    
    // Leaderboard Management
    Route::get('/users-by-contribution', [SuperAdminController::class, 'getUsersByContribution']);
    Route::post('/users/{id}/toggle-best', [SuperAdminController::class, 'toggleBestStatus']);

    // CMS - Hub Cards
    Route::get('/hub-cards', [HubCardController::class, 'index']);
    Route::post('/hub-cards/{id}', [HubCardController::class, 'update']);

    // Audit Logs
    Route::get('/audit-logs', [SuperAdminController::class, 'getAuditLogs']);
});

// Public CMS data
Route::get('/public/hub-cards', [HubCardController::class, 'index']);

// Public featured performers
Route::get('/public/featured-performers', [SuperAdminController::class, 'getFeaturedPerformers']);
