<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\EducationController;
use App\Http\Controllers\ExperienceController;
use App\Http\Controllers\PortfolioCommonController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ShippingAddressController;
use Illuminate\Support\Facades\Route;

//Register
Route::post('/register/investor', [RegistrationController::class, 'registerInvestor']);
Route::post('/register/generaluser', [RegistrationController::class, 'registerGeneralUser']);
Route::post('/register/both', [RegistrationController::class, 'registerBoth']);
Route::middleware('auth:sanctum')->post('/change-password', [AuthController::class, 'changePassword']);

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
    Route::get('/profile/selling-eligibility', [ProfileController::class, 'getSellingEligibility']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/upload-profile-image', [ProfileController::class, 'updateProfileImage']);
    Route::post('/profile/upload-cover-image', [ProfileController::class, 'updateCoverImage']);
    Route::get('/profile/{userId}', [ProfileController::class, 'getPublicProfile']);

    // Experience routes
    Route::apiResource('experiences', ExperienceController::class);

    // Education routes
    Route::apiResource('educations', EducationController::class);

    //Card
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/cards', [CardController::class, 'index']);
        Route::post('/cards', [CardController::class, 'store']);
        Route::delete('/cards/{id}', [CardController::class, 'destroy']);
        Route::post('/cards/{id}/default', [CardController::class, 'setDefault']);
    });

    //Bank Details
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/bank-details', [\App\Http\Controllers\BankDetailController::class, 'index']);
        Route::post('/bank-details', [\App\Http\Controllers\BankDetailController::class, 'store']);
        Route::delete('/bank-details/{id}', [\App\Http\Controllers\BankDetailController::class, 'destroy']);
        Route::post('/bank-details/{id}/default', [\App\Http\Controllers\BankDetailController::class, 'setDefault']);
    });

    //Shipping Address
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('shipping-addresses', ShippingAddressController::class);
        Route::post('/shipping-addresses/{id}/set-default', [ShippingAddressController::class, 'setDefault']);
        Route::get('/shipping-addresses/default', [ShippingAddressController::class, 'getDefault']);
    });

    //Common Details for public view portfolio
    Route::middleware('auth:sanctum')->get('/portfolio', [PortfolioCommonController::class, 'getUserPublicView']);
    Route::get('/portfolio/{userId}', [PortfolioCommonController::class, 'getUserPublicView']);
});
