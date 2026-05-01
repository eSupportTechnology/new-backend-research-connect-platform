<?php

use App\Http\Controllers\HireRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/hire-requests',                    [HireRequestController::class, 'store']);
    Route::get('/hire-requests/incoming',            [HireRequestController::class, 'incoming']);
    Route::get('/hire-requests/outgoing',            [HireRequestController::class, 'outgoing']);
    Route::patch('/hire-requests/{id}/accept',       [HireRequestController::class, 'accept']);
    Route::patch('/hire-requests/{id}/decline',      [HireRequestController::class, 'decline']);
    Route::patch('/hire-requests/{id}/complete',     [HireRequestController::class, 'complete']);
});