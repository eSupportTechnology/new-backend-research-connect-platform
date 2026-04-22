<?php

use App\Http\Controllers\MembershipController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('membership')->group(function () {
    Route::get('/info', [MembershipController::class, 'info']);
    Route::post('/payment-params', [MembershipController::class, 'getPaymentParams']);
});