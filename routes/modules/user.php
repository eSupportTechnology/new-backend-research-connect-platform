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

    // Deleting an account is an authenticated, super-admin-only action — it also
    // needs the caller's identity to stamp deleted_by.
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
});

Route::put('/users/{id}', [UserController::class, 'update']);

Route::patch('/users/{id}/status', [UserController::class, 'toggleStatus']);

Route::post('/users/bulk-action', [UserController::class, 'bulkAction']);
