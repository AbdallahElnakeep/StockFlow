<?php

use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [App\Http\Controllers\Api\AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'active.user'])->group(function () {
    Route::post('/auth/logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);
    Route::get('/bootstrap', App\Http\Controllers\Api\BootstrapController::class);
    Route::post('/sync', [App\Http\Controllers\Api\SyncController::class, 'store']);
    Route::get('/my/summary', App\Http\Controllers\Api\RepSummaryController::class);
});
