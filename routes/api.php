<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RefreshController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WorkspaceController;
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

/*
|--------------------------------------------------------------------------
| Rotas autenticadas
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());

    // AUTH-04 — Perfil do usuário
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // CHAN-01 — Workspaces
    Route::post('/workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');

    // CHAN-02/03 — Canais dentro de um workspace
    Route::prefix('workspaces/{workspace}')->group(function () {
        Route::get('/channels', [ChannelController::class, 'index'])->name('channels.index');
        Route::post('/channels', [ChannelController::class, 'store'])
            ->middleware('permission:channel.create')
            ->name('channels.store');
    });

    /*
    |----------------------------------------------------------------------
    | Administração (somente TI) — AUTH-02/03, RBAC-03
    |----------------------------------------------------------------------
    */
    Route::prefix('admin')->group(function () {
        Route::post('/users', [AdminUserController::class, 'store'])
            ->middleware('permission:user.create')
            ->name('admin.users.store');

        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])
            ->middleware('permission:user.deactivate')
            ->name('admin.users.destroy');
    });
});
