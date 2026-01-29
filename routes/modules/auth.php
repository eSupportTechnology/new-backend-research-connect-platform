<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;

//Register
Route::post('/register/investor', [RegistrationController::class, 'registerInvestor']);
Route::post('/register/generaluser', [RegistrationController::class, 'registerGeneralUser']);
Route::post('/register/both', [RegistrationController::class, 'registerBoth']);

//Login
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});
