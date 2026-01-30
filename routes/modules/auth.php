<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EducationController;
use App\Http\Controllers\ExperienceController;
use App\Http\Controllers\ProfileController;
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

//Portfolio
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'index']);
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/upload-image', [ProfileController::class, 'updateProfileImage']);
    Route::post('/profile/upload-image', [ProfileController::class, 'updateCoverImage']);


    // Experience routes
    Route::apiResource('experiences', ExperienceController::class);

    // Education routes
    Route::apiResource('educations', EducationController::class);
});
