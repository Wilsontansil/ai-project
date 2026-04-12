<?php

use App\Http\Controllers\Backoffice\AIAgentController;
use App\Http\Controllers\Backoffice\AuthController;
use App\Http\Controllers\Backoffice\DataModelController;
use App\Http\Controllers\Backoffice\ForbiddenBehaviourController;
use App\Http\Controllers\Backoffice\DashboardController;
use App\Http\Controllers\Backoffice\EscalationController;
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
    Route::get('/backoffice/ai-agent', [AIAgentController::class, 'index'])->name('backoffice.ai-agent');
    Route::post('/backoffice/ai-agent', [AIAgentController::class, 'update'])->name('backoffice.ai-agent.update');

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

    Route::get('/backoffice/forbidden-behaviours', [ForbiddenBehaviourController::class, 'index'])->name('backoffice.forbidden.index');
    Route::get('/backoffice/forbidden-behaviours/create', [ForbiddenBehaviourController::class, 'create'])->name('backoffice.forbidden.create');
    Route::post('/backoffice/forbidden-behaviours', [ForbiddenBehaviourController::class, 'store'])->name('backoffice.forbidden.store');
    Route::get('/backoffice/forbidden-behaviours/{forbidden_behaviour}/edit', [ForbiddenBehaviourController::class, 'edit'])->name('backoffice.forbidden.edit');
    Route::put('/backoffice/forbidden-behaviours/{forbidden_behaviour}', [ForbiddenBehaviourController::class, 'update'])->name('backoffice.forbidden.update');
    Route::delete('/backoffice/forbidden-behaviours/{forbidden_behaviour}', [ForbiddenBehaviourController::class, 'destroy'])->name('backoffice.forbidden.destroy');

    Route::get('/backoffice/escalations', [EscalationController::class, 'index'])->name('backoffice.escalations.index');
    Route::post('/backoffice/escalations/{escalation}/read', [EscalationController::class, 'markRead'])->name('backoffice.escalations.markRead');
    Route::post('/backoffice/escalations/{escalation}/resolve', [EscalationController::class, 'resolve'])->name('backoffice.escalations.resolve');
    Route::post('/backoffice/escalations/mark-all-read', [EscalationController::class, 'markAllRead'])->name('backoffice.escalations.markAllRead');
    Route::get('/backoffice/escalations/unread-count', [EscalationController::class, 'unreadCount'])->name('backoffice.escalations.unreadCount');

    Route::get('/backoffice/settings', [SettingController::class, 'index'])->name('backoffice.settings.index');
    Route::post('/backoffice/settings', [SettingController::class, 'update'])->name('backoffice.settings.update');

    Route::post('/backoffice/logout', [AuthController::class, 'logout'])->name('logout');
});
