<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ServerController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('servers', ServerController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::post('/servers/{server}/websites', [ServerController::class, 'websiteCreate'])->name('servers.websites.store');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
