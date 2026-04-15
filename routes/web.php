<?php

use App\Http\Controllers\Backoffice\AuthController;
use App\Http\Controllers\Backoffice\ChatAgentController;
use App\Http\Controllers\Backoffice\DataModelController;
use App\Http\Controllers\Backoffice\ForbiddenBehaviourController;
use App\Http\Controllers\Backoffice\DashboardController;
use App\Http\Controllers\Backoffice\DatabaseConnectionController;
use App\Http\Controllers\Backoffice\SettingController;
use App\Http\Controllers\Backoffice\ToolController;
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
    Route::get('/backoffice/customer/{customer}/chat', [DashboardController::class, 'chat'])->name('backoffice.customer.chat');
    Route::get('/backoffice/chat-agents', [ChatAgentController::class, 'index'])->name('backoffice.chat-agents.index');
    Route::get('/backoffice/chat-agents/create', [ChatAgentController::class, 'create'])->name('backoffice.chat-agents.create');
    Route::post('/backoffice/chat-agents', [ChatAgentController::class, 'store'])->name('backoffice.chat-agents.store');
    Route::get('/backoffice/chat-agents/{chatAgent}/edit', [ChatAgentController::class, 'edit'])->name('backoffice.chat-agents.edit');
    Route::put('/backoffice/chat-agents/{chatAgent}', [ChatAgentController::class, 'update'])->name('backoffice.chat-agents.update');
    Route::delete('/backoffice/chat-agents/{chatAgent}', [ChatAgentController::class, 'destroy'])->name('backoffice.chat-agents.destroy');
    Route::post('/backoffice/chat-agents/{chatAgent}/duplicate', [ChatAgentController::class, 'duplicate'])->name('backoffice.chat-agents.duplicate');

    // Forbidden behaviours scoped per agent
    Route::get('/backoffice/chat-agents/{chatAgent}/forbidden/create', [ForbiddenBehaviourController::class, 'create'])->name('backoffice.forbidden.create');
    Route::post('/backoffice/chat-agents/{chatAgent}/forbidden', [ForbiddenBehaviourController::class, 'store'])->name('backoffice.forbidden.store');
    Route::get('/backoffice/chat-agents/{chatAgent}/forbidden/{forbidden_behaviour}/edit', [ForbiddenBehaviourController::class, 'edit'])->name('backoffice.forbidden.edit');
    Route::put('/backoffice/chat-agents/{chatAgent}/forbidden/{forbidden_behaviour}', [ForbiddenBehaviourController::class, 'update'])->name('backoffice.forbidden.update');
    Route::delete('/backoffice/chat-agents/{chatAgent}/forbidden/{forbidden_behaviour}', [ForbiddenBehaviourController::class, 'destroy'])->name('backoffice.forbidden.destroy');

    Route::get('/backoffice/tools', [ToolController::class, 'index'])->name('backoffice.tools.index');
    Route::get('/backoffice/tools/create', [ToolController::class, 'create'])->name('backoffice.tools.create');
    Route::post('/backoffice/tools', [ToolController::class, 'store'])->name('backoffice.tools.store');
    Route::get('/backoffice/tools/{tool}/edit', [ToolController::class, 'edit'])->name('backoffice.tools.edit');
    Route::put('/backoffice/tools/{tool}', [ToolController::class, 'update'])->name('backoffice.tools.update');
    Route::delete('/backoffice/tools/{tool}', [ToolController::class, 'destroy'])->name('backoffice.tools.destroy');
    Route::post('/backoffice/tools/test-endpoint', [ToolController::class, 'testEndpoint'])->name('backoffice.tools.testEndpoint');

    Route::get('/backoffice/data-models', [DataModelController::class, 'index'])->name('backoffice.data-models.index');
    Route::get('/backoffice/data-models/create', [DataModelController::class, 'create'])->name('backoffice.data-models.create');
    Route::post('/backoffice/data-models', [DataModelController::class, 'store'])->name('backoffice.data-models.store');
    Route::get('/backoffice/data-models/{dataModel}/edit', [DataModelController::class, 'edit'])->name('backoffice.data-models.edit');
    Route::put('/backoffice/data-models/{dataModel}', [DataModelController::class, 'update'])->name('backoffice.data-models.update');
    Route::delete('/backoffice/data-models/{dataModel}', [DataModelController::class, 'destroy'])->name('backoffice.data-models.destroy');

    Route::get('/backoffice/settings', [SettingController::class, 'index'])->name('backoffice.settings.index');
    Route::post('/backoffice/settings', [SettingController::class, 'update'])->name('backoffice.settings.update');

    Route::get('/backoffice/database-connections', [DatabaseConnectionController::class, 'index'])->name('backoffice.database-connections.index');
    Route::get('/backoffice/database-connections/create', [DatabaseConnectionController::class, 'create'])->name('backoffice.database-connections.create');
    Route::post('/backoffice/database-connections', [DatabaseConnectionController::class, 'store'])->name('backoffice.database-connections.store');
    Route::get('/backoffice/database-connections/{databaseConnection}/edit', [DatabaseConnectionController::class, 'edit'])->name('backoffice.database-connections.edit');
    Route::put('/backoffice/database-connections/{databaseConnection}', [DatabaseConnectionController::class, 'update'])->name('backoffice.database-connections.update');
    Route::delete('/backoffice/database-connections/{databaseConnection}', [DatabaseConnectionController::class, 'destroy'])->name('backoffice.database-connections.destroy');
    Route::post('/backoffice/database-connections/{databaseConnection}/test', [DatabaseConnectionController::class, 'testConnection'])->name('backoffice.database-connections.test');

    Route::post('/backoffice/logout', [AuthController::class, 'logout'])->name('logout');
});
