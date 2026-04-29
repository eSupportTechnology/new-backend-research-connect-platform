<?php

use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\AdminPaymentController;
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

    // Dashboard Export
    Route::get('/dashboard/export', [SuperAdminController::class, 'exportReport']);

    // Payments & Revenue
    Route::get('/payments/overview',              [AdminPaymentController::class, 'getOverview']);
    Route::get('/payments/orders',                [AdminPaymentController::class, 'getOrders']);
    Route::get('/payments/ads',                   [AdminPaymentController::class, 'getAdPayments']);
    Route::get('/payments/export',                [AdminPaymentController::class, 'exportPayments']);
    Route::get('/payments/transaction-analytics', [AdminPaymentController::class, 'getTransactionAnalytics']);
    Route::get('/payments/payouts',               [AdminPaymentController::class, 'getPendingPayouts']);
    Route::put('/payments/payouts/{id}/mark',     [AdminPaymentController::class, 'markPayout']);
    Route::post('/payments/payouts/bulk-mark',    [AdminPaymentController::class, 'bulkMarkPayout']);
});

// Public CMS data
Route::get('/public/hub-cards', [HubCardController::class, 'index']);

// Public featured performers
Route::get('/public/featured-performers', [SuperAdminController::class, 'getFeaturedPerformers']);

// Public platform stats (homepage counters)
Route::get('/public/platform-stats', [SuperAdminController::class, 'getPlatformStats']);
