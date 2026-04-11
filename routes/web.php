<?php

use App\Http\Controllers\Backoffice\AIAgentController;
use App\Http\Controllers\Backoffice\AuthController;
use App\Http\Controllers\Backoffice\CaseController;
use App\Http\Controllers\Backoffice\DashboardController;
use App\Http\Controllers\Backoffice\EscalationController;
use App\Http\Controllers\Backoffice\KnowledgeBaseController;
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

    Route::get('/backoffice/cases', [CaseController::class, 'index'])->name('backoffice.cases.index');
    Route::get('/backoffice/cases/create', [CaseController::class, 'create'])->name('backoffice.cases.create');
    Route::post('/backoffice/cases', [CaseController::class, 'store'])->name('backoffice.cases.store');
    Route::get('/backoffice/cases/{case}/edit', [CaseController::class, 'edit'])->name('backoffice.cases.edit');
    Route::put('/backoffice/cases/{case}', [CaseController::class, 'update'])->name('backoffice.cases.update');
    Route::delete('/backoffice/cases/{case}', [CaseController::class, 'destroy'])->name('backoffice.cases.destroy');

    Route::get('/backoffice/escalations', [EscalationController::class, 'index'])->name('backoffice.escalations.index');
    Route::post('/backoffice/escalations/{escalation}/read', [EscalationController::class, 'markRead'])->name('backoffice.escalations.markRead');
    Route::post('/backoffice/escalations/{escalation}/resolve', [EscalationController::class, 'resolve'])->name('backoffice.escalations.resolve');
    Route::post('/backoffice/escalations/mark-all-read', [EscalationController::class, 'markAllRead'])->name('backoffice.escalations.markAllRead');
    Route::get('/backoffice/escalations/unread-count', [EscalationController::class, 'unreadCount'])->name('backoffice.escalations.unreadCount');

    Route::post('/backoffice/logout', [AuthController::class, 'logout'])->name('logout');

    // Knowledge Base
    Route::get('/backoffice/knowledge', [KnowledgeBaseController::class, 'index'])->name('backoffice.knowledge.index');
    Route::get('/backoffice/knowledge/create', [KnowledgeBaseController::class, 'create'])->name('backoffice.knowledge.create');
    Route::post('/backoffice/knowledge', [KnowledgeBaseController::class, 'store'])->name('backoffice.knowledge.store');
    Route::get('/backoffice/knowledge/upload', [KnowledgeBaseController::class, 'uploadForm'])->name('backoffice.knowledge.upload');
    Route::post('/backoffice/knowledge/upload', [KnowledgeBaseController::class, 'upload'])->name('backoffice.knowledge.upload.submit');
    Route::get('/backoffice/knowledge/{knowledge}/edit', [KnowledgeBaseController::class, 'edit'])->name('backoffice.knowledge.edit');
    Route::put('/backoffice/knowledge/{knowledge}', [KnowledgeBaseController::class, 'update'])->name('backoffice.knowledge.update');
    Route::delete('/backoffice/knowledge/{knowledge}', [KnowledgeBaseController::class, 'destroy'])->name('backoffice.knowledge.destroy');

    // AI Learned Memories
    Route::post('/backoffice/knowledge/memory/{memory}/approve', [KnowledgeBaseController::class, 'approveMemory'])->name('backoffice.knowledge.memory.approve');
    Route::post('/backoffice/knowledge/memory/{memory}/reject', [KnowledgeBaseController::class, 'rejectMemory'])->name('backoffice.knowledge.memory.reject');
    Route::delete('/backoffice/knowledge/memory/{memory}', [KnowledgeBaseController::class, 'destroyMemory'])->name('backoffice.knowledge.memory.destroy');
});
