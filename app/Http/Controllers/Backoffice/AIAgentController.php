<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\AgentCase;
use App\Models\Tool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AIAgentController extends Controller
{
    public function index(Request $request): View
    {
        $activeCases = Schema::hasTable('agent_cases')
            ? AgentCase::query()->where('is_active', true)->count()
            : 0;

        $botName = 'xoneBot';
        if (Schema::hasTable('tools')) {
            $config = Tool::query()->where('tool_name', '_bot_config')->first();
            $botName = $config?->meta['bot_name'] ?? $botName;
        }

        return view('backoffice.ai-agent', [
            'aiInfo' => [
                'model' => 'gpt-4o-mini',
                'bot_name' => $botName,
                'agent_kode' => config('services.agent.kode', 'PG'),
                'agent_id' => config('services.agent.id', 1),
                'max_tokens' => 420,
                'active_cases' => $activeCases,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bot_name' => ['required', 'string', 'max:60'],
        ]);

        Tool::query()->updateOrCreate(
            ['tool_name' => '_bot_config'],
            [
                'display_name' => 'Bot Config',
                'description' => 'General bot configuration',
                'class_name' => '',
                'slug' => '_bot-config',
                'is_enabled' => false,
                'meta' => ['bot_name' => trim($data['bot_name'])],
            ]
        );

        return back()->with('success', 'Bot name berhasil diperbarui.');
    }

    public function showTool(Tool $tool): View
    {
        return view('backoffice.tools.tool-detail', [
            'tool' => $tool,
            'boActive' => 'ai-agent',
            'currentTool' => $tool->tool_name,
        ]);
    }

    public function updateTool(Request $request, Tool $tool): RedirectResponse
    {
        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $tool->update([
            'display_name' => trim((string) $data['display_name']),
            'description' => trim((string) ($data['description'] ?? '')),
            'is_enabled' => $request->boolean('is_enabled'),
        ]);

        return back()->with('success', $tool->display_name . ' setting berhasil disimpan.');
    }
}

