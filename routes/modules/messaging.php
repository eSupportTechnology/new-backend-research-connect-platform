<?php

use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/messages/send',          [MessageController::class, 'send']);
    Route::get('/messages/inbox',          [MessageController::class, 'inbox']);
    Route::patch('/messages/{id}/read',    [MessageController::class, 'markRead']);
    Route::delete('/messages/{id}',        [MessageController::class, 'destroy']);
});