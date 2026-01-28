<?php

use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;


Route::post('/register/investor', [RegistrationController::class, 'registerInvestor']);
Route::post('/register/generaluser', [RegistrationController::class, 'registerGeneralUser']);
Route::post('/register/both', [RegistrationController::class, 'registerBoth']);
