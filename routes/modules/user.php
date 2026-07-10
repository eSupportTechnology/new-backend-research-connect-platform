<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'store']);

// Deleted Users portal (recycle bin) — declared before /users/{id} to avoid conflicts.
// Requires authentication so the super-admin authorization check can resolve the user.
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users/deleted', [UserController::class, 'deleted']);
    Route::post('/users/{id}/restore', [UserController::class, 'restore']);
    Route::delete('/users/{id}/force', [UserController::class, 'forceDelete']);
});

Route::put('/users/{id}', [UserController::class, 'update']);
Route::delete('/users/{id}', [UserController::class, 'destroy']);

Route::patch('/users/{id}/status', [UserController::class, 'toggleStatus']);

Route::post('/users/bulk-action', [UserController::class, 'bulkAction']);
