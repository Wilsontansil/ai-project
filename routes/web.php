<?php

use App\Http\Controllers\Backoffice\AuthController;
use App\Http\Controllers\Backoffice\ChatAgentController;
use App\Http\Controllers\Backoffice\DataModelController;
use App\Http\Controllers\Backoffice\AgentRuleController;
use App\Http\Controllers\Backoffice\DashboardController;
use App\Http\Controllers\Backoffice\DatabaseConnectionController;
use App\Http\Controllers\Backoffice\LocaleController;
use App\Http\Controllers\Backoffice\MetricsController;
use App\Http\Controllers\Backoffice\SettingController;
use App\Http\Controllers\Backoffice\ToolController;
use App\Http\Controllers\Backoffice\UserController;
use Illuminate\Support\Facades\Route;

$entryRedirect = fn () => redirect()->route('login');

Route::get('/', $entryRedirect);
Route::get('/aiproject', $entryRedirect);
Route::middleware('set.locale')->post('/backoffice/locale', [LocaleController::class, 'update'])->name('backoffice.locale.update');

Route::middleware(['set.locale', 'guest'])->group(function () {
    Route::get('/backoffice/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/backoffice/login', [AuthController::class, 'login'])->name('backoffice.login.submit');
});

Route::middleware(['set.locale', 'auth', 'single.session'])->group(function () {
    Route::get('/backoffice', [DashboardController::class, 'index'])->name('backoffice.dashboard');
    Route::get('/backoffice/customer/{customer}/chat', [DashboardController::class, 'chat'])->name('backoffice.customer.chat');

    // Chat agents (admin only)
    Route::middleware('permission:manage agents')->group(function () {
        Route::get('/backoffice/chat-agents', [ChatAgentController::class, 'index'])->name('backoffice.chat-agents.index');
        Route::get('/backoffice/chat-agents/create', [ChatAgentController::class, 'create'])->name('backoffice.chat-agents.create');
        Route::post('/backoffice/chat-agents', [ChatAgentController::class, 'store'])->name('backoffice.chat-agents.store');
        Route::get('/backoffice/chat-agents/{chatAgent}/edit', [ChatAgentController::class, 'edit'])->name('backoffice.chat-agents.edit');
        Route::put('/backoffice/chat-agents/{chatAgent}', [ChatAgentController::class, 'update'])->name('backoffice.chat-agents.update');
        Route::delete('/backoffice/chat-agents/{chatAgent}', [ChatAgentController::class, 'destroy'])->name('backoffice.chat-agents.destroy');
        Route::post('/backoffice/chat-agents/{chatAgent}/duplicate', [ChatAgentController::class, 'duplicate'])->name('backoffice.chat-agents.duplicate');
    });

    // Agent rules scoped per agent (admin only)
    Route::middleware('permission:manage agent-rules')->group(function () {
        Route::get('/backoffice/chat-agents/{chatAgent}/rules/create', [AgentRuleController::class, 'create'])->name('backoffice.agent-rules.create');
        Route::post('/backoffice/chat-agents/{chatAgent}/rules', [AgentRuleController::class, 'store'])->name('backoffice.agent-rules.store');
        Route::get('/backoffice/chat-agents/{chatAgent}/rules/{agentRule}/edit', [AgentRuleController::class, 'edit'])->name('backoffice.agent-rules.edit');
        Route::put('/backoffice/chat-agents/{chatAgent}/rules/{agentRule}', [AgentRuleController::class, 'update'])->name('backoffice.agent-rules.update');
        Route::delete('/backoffice/chat-agents/{chatAgent}/rules/{agentRule}', [AgentRuleController::class, 'destroy'])->name('backoffice.agent-rules.destroy');
    });

    // Tools (admin only)
    Route::middleware('permission:manage tools')->group(function () {
        Route::get('/backoffice/tools', [ToolController::class, 'index'])->name('backoffice.tools.index');
        Route::get('/backoffice/tools/create', [ToolController::class, 'create'])->name('backoffice.tools.create');
        Route::post('/backoffice/tools', [ToolController::class, 'store'])->name('backoffice.tools.store');
        Route::get('/backoffice/tools/{tool}/edit', [ToolController::class, 'edit'])->name('backoffice.tools.edit');
        Route::put('/backoffice/tools/{tool}', [ToolController::class, 'update'])->name('backoffice.tools.update');
        Route::delete('/backoffice/tools/{tool}', [ToolController::class, 'destroy'])->name('backoffice.tools.destroy');
        Route::post('/backoffice/tools/test-endpoint', [ToolController::class, 'testEndpoint'])->name('backoffice.tools.testEndpoint');
    });

    // Data models (admin only)
    Route::middleware('permission:manage data-models')->group(function () {
        Route::get('/backoffice/data-models', [DataModelController::class, 'index'])->name('backoffice.data-models.index');
        Route::get('/backoffice/data-models/create', [DataModelController::class, 'create'])->name('backoffice.data-models.create');
        Route::post('/backoffice/data-models', [DataModelController::class, 'store'])->name('backoffice.data-models.store');
        Route::get('/backoffice/data-models/{dataModel}/edit', [DataModelController::class, 'edit'])->name('backoffice.data-models.edit');
        Route::put('/backoffice/data-models/{dataModel}', [DataModelController::class, 'update'])->name('backoffice.data-models.update');
        Route::delete('/backoffice/data-models/{dataModel}', [DataModelController::class, 'destroy'])->name('backoffice.data-models.destroy');
    });

    // Settings (admin only)
    Route::middleware('permission:manage settings')->group(function () {
        Route::get('/backoffice/settings', [SettingController::class, 'index'])->name('backoffice.settings.index');
        Route::post('/backoffice/settings', [SettingController::class, 'update'])->name('backoffice.settings.update');
    });

    // Database connections (admin only)
    Route::middleware('permission:manage database-connections')->group(function () {
        Route::get('/backoffice/database-connections', [DatabaseConnectionController::class, 'index'])->name('backoffice.database-connections.index');
        Route::get('/backoffice/database-connections/create', [DatabaseConnectionController::class, 'create'])->name('backoffice.database-connections.create');
        Route::post('/backoffice/database-connections', [DatabaseConnectionController::class, 'store'])->name('backoffice.database-connections.store');
        Route::get('/backoffice/database-connections/{databaseConnection}/edit', [DatabaseConnectionController::class, 'edit'])->name('backoffice.database-connections.edit');
        Route::put('/backoffice/database-connections/{databaseConnection}', [DatabaseConnectionController::class, 'update'])->name('backoffice.database-connections.update');
        Route::delete('/backoffice/database-connections/{databaseConnection}', [DatabaseConnectionController::class, 'destroy'])->name('backoffice.database-connections.destroy');
        Route::post('/backoffice/database-connections/{databaseConnection}/test', [DatabaseConnectionController::class, 'testConnection'])->name('backoffice.database-connections.test');
    });

    // Metrics
    Route::middleware('permission:view metrics')->group(function () {
        Route::get('/backoffice/metrics', [MetricsController::class, 'index'])->name('backoffice.metrics.index');
    });

    // User management (admin only)
    Route::middleware('permission:manage users')->group(function () {
        Route::get('/backoffice/users', [UserController::class, 'index'])->name('backoffice.users.index');
        Route::get('/backoffice/users/create', [UserController::class, 'create'])->name('backoffice.users.create');
        Route::post('/backoffice/users', [UserController::class, 'store'])->name('backoffice.users.store');
        Route::get('/backoffice/users/{user}/edit', [UserController::class, 'edit'])->name('backoffice.users.edit');
        Route::put('/backoffice/users/{user}', [UserController::class, 'update'])->name('backoffice.users.update');
        Route::delete('/backoffice/users/{user}', [UserController::class, 'destroy'])->name('backoffice.users.destroy');
    });

    Route::post('/backoffice/logout', [AuthController::class, 'logout'])->name('logout');
});
