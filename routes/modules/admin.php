<?php

use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\AdminPaymentController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\AdvertisementController;
use App\Http\Controllers\Cms\HubCardController;
use App\Http\Controllers\VideoUploadPaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('super-admin')->group(function () {
    // Video upload fee settings
    Route::get('/video-upload-fee',    [VideoUploadPaymentController::class, 'getFee']);
    Route::put('/video-upload-fee',    [VideoUploadPaymentController::class, 'updateFee']);
    Route::get('/video-upload-payments', [VideoUploadPaymentController::class, 'adminIndex']);

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
    Route::get('/payments/overdue-deliveries',    [AdminPaymentController::class, 'getOverdueDeliveries']);

    // Membership Management
    Route::get('/memberships',                      [SuperAdminController::class, 'getMembershipPayments']);
    Route::get('/memberships/pricing',              [SuperAdminController::class, 'getMembershipPricing']);
    Route::put('/memberships/pricing',              [SuperAdminController::class, 'updateMembershipPricing']);

    // Advertisement Pricing
    Route::get('/advertisements/pricing',           [AdvertisementController::class, 'getPricing']);
    Route::put('/advertisements/pricing',           [AdvertisementController::class, 'updatePricing']);

    // Hire Request Overview
    Route::get('/hire-requests',               [SuperAdminController::class, 'getHireRequests']);

    // Student Verification
    Route::get('/student-verifications',                        [SuperAdminController::class, 'getStudentVerifications']);
    Route::get('/student-verifications/{id}/certificate',      [SuperAdminController::class, 'serveCertificate']);
    Route::put('/student-verifications/{id}',                  [SuperAdminController::class, 'updateStudentVerification']);
    Route::delete('/student-verifications/{id}',               [SuperAdminController::class, 'deleteStudentVerification']);

    // Admin Notifications
    Route::get('/notifications',               [AdminNotificationController::class, 'index']);
    Route::put('/notifications/read-all',      [AdminNotificationController::class, 'markAllRead']);
    Route::delete('/notifications/clear-read', [AdminNotificationController::class, 'clearRead']);
    Route::put('/notifications/{id}/read',     [AdminNotificationController::class, 'markRead']);
    Route::delete('/notifications/{id}',       [AdminNotificationController::class, 'destroy']);
});

// Public CMS data
Route::get('/public/hub-cards', [HubCardController::class, 'index']);

// Public featured performers
Route::get('/public/featured-performers', [SuperAdminController::class, 'getFeaturedPerformers']);

// All active innovators / researchers (at least 1 submission)
Route::get('/public/all-performers', [SuperAdminController::class, 'getAllPerformers']);

// Public platform stats (homepage counters)
Route::get('/public/platform-stats', [SuperAdminController::class, 'getPlatformStats']);
