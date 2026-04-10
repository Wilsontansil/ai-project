<?php

use App\Http\Controllers\Backoffice\AuthController;
use App\Http\Controllers\Backoffice\DashboardController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/backoffice');

Route::middleware('guest')->group(function () {
    Route::get('/backoffice/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/backoffice/login', [AuthController::class, 'login'])->name('backoffice.login.submit');
});

Route::middleware('auth')->group(function () {
    Route::get('/backoffice', [DashboardController::class, 'index'])->name('backoffice.dashboard');
    Route::post('/backoffice/logout', [AuthController::class, 'logout'])->name('logout');
});
