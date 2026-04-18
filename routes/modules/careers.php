<?php

use App\Http\Controllers\JobController;
use Illuminate\Support\Facades\Route;

/**
 * Career / Job Posts Routes
 */

// Public routes
Route::get('/jobs', [JobController::class, 'index']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/jobs', [JobController::class, 'store']);
    Route::delete('/jobs/{id}', [JobController::class, 'destroy']);
    
    // Admin only routes
    Route::middleware('role:admin,super_admin,superadmin')->group(function () {
        Route::get('/admin/jobs', [JobController::class, 'getAdminJobs']);
        Route::patch('/admin/jobs/{id}/status', [JobController::class, 'updateStatus']);
    });
});
