<?php

use App\Http\Controllers\SuperAdminController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('super-admin')->group(function () {
    Route::get('/dashboard/stats', [SuperAdminController::class, 'getDashboardStats']);
});
