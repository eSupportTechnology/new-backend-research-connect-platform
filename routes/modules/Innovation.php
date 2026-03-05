<?php

use App\Http\Controllers\Api\InnovationController;
use Illuminate\Support\Facades\Route;

Route::post('/innovations', [InnovationController::class, 'store']);
