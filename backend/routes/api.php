<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Public route: Login (mengembalikan bearer token)
Route::post('/login', [AuthController::class, 'login'])->name('login');

// Protected routes (wajib token Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Resource Asset CRUD
    Route::apiResource('assets', AssetController::class);
});
