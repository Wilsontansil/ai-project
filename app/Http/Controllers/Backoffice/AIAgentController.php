<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\AgentRule;
use App\Models\ProjectSetting;
use App\Models\Tool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AIAgentController extends Controller
{
    public function index(Request $request): View
    {
        $activeRules = Schema::hasTable('agent_rules')
            ? AgentRule::query()->where('is_active', true)->count()
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
                'agent_kode' => ProjectSetting::getValue('agent_kode', config('services.agent.kode', 'PG')),
                'agent_id' => ProjectSetting::getValue('agent_id', config('services.agent.id', 1)),
                'max_tokens' => 420,
                'active_rules' => $activeRules,
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
                'slug' => '_bot-config',
                'is_enabled' => false,
                'meta' => ['bot_name' => trim($data['bot_name'])],
            ]
        );

        return back()->with('success', 'Bot name berhasil diperbarui.');
    }
}

