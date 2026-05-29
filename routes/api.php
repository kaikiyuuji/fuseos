<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RefreshController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Autenticação (Sanctum)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('/login', LoginController::class)->name('auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', LogoutController::class)->name('auth.logout');
        Route::post('/refresh', RefreshController::class)->name('auth.refresh');
    });
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
