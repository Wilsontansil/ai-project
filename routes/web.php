<?php

use App\Http\Controllers\Backoffice\AIAgentController;
use App\Http\Controllers\Backoffice\AuthController;
use App\Http\Controllers\Backoffice\CaseController;
use App\Http\Controllers\Backoffice\DashboardController;
use Illuminate\Support\Facades\Route;

$entryRedirect = fn () => redirect()->route('login');

Route::get('/', $entryRedirect);
Route::get('/aiproject', $entryRedirect);

Route::middleware('guest')->group(function () {
    Route::get('/backoffice/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/backoffice/login', [AuthController::class, 'login'])->name('backoffice.login.submit');
});

Route::middleware('auth')->group(function () {
    Route::get('/backoffice', [DashboardController::class, 'index'])->name('backoffice.dashboard');
    Route::get('/backoffice/ai-agent', [AIAgentController::class, 'index'])->name('backoffice.ai-agent');

    Route::get('/backoffice/tools/reset-password', [AIAgentController::class, 'showTool'])
        ->defaults('toolSlug', 'reset-password')
        ->name('backoffice.tools.reset-password');
    Route::post('/backoffice/tools/reset-password', [AIAgentController::class, 'updateTool'])
        ->defaults('toolSlug', 'reset-password')
        ->name('backoffice.tools.reset-password.update');

    Route::get('/backoffice/tools/check-suspend', [AIAgentController::class, 'showTool'])
        ->defaults('toolSlug', 'check-suspend')
        ->name('backoffice.tools.check-suspend');
    Route::post('/backoffice/tools/check-suspend', [AIAgentController::class, 'updateTool'])
        ->defaults('toolSlug', 'check-suspend')
        ->name('backoffice.tools.check-suspend.update');

    Route::get('/backoffice/tools/register', [AIAgentController::class, 'showTool'])
        ->defaults('toolSlug', 'register')
        ->name('backoffice.tools.register');
    Route::post('/backoffice/tools/register', [AIAgentController::class, 'updateTool'])
        ->defaults('toolSlug', 'register')
        ->name('backoffice.tools.register.update');

    Route::get('/backoffice/cases', [CaseController::class, 'index'])->name('backoffice.cases.index');
    Route::get('/backoffice/cases/create', [CaseController::class, 'create'])->name('backoffice.cases.create');
    Route::post('/backoffice/cases', [CaseController::class, 'store'])->name('backoffice.cases.store');
    Route::get('/backoffice/cases/{case}/edit', [CaseController::class, 'edit'])->name('backoffice.cases.edit');
    Route::put('/backoffice/cases/{case}', [CaseController::class, 'update'])->name('backoffice.cases.update');
    Route::delete('/backoffice/cases/{case}', [CaseController::class, 'destroy'])->name('backoffice.cases.destroy');

    Route::post('/backoffice/logout', [AuthController::class, 'logout'])->name('logout');
});
