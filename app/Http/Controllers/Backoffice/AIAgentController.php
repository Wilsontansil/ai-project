<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\AgentCase;
use App\Models\ToolSetting;
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
        if (Schema::hasTable('tool_settings')) {
            $config = ToolSetting::query()->where('tool_name', '_bot_config')->first();
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

        ToolSetting::query()->updateOrCreate(
            ['tool_name' => '_bot_config'],
            [
                'display_name' => 'Bot Config',
                'description' => 'General bot configuration',
                'meta' => ['bot_name' => trim($data['bot_name'])],
            ]
        );

        return back()->with('success', 'Bot name berhasil diperbarui.');
    }

    public function showTool(string $toolSlug): View
    {
        $slugMap = [
            'reset-password' => 'resetPassword',
            'check-suspend' => 'checkSuspend',
            'register' => 'register',
        ];

        $viewMap = [
            'reset-password' => 'backoffice.tools.reset-password',
            'check-suspend' => 'backoffice.tools.check-suspend',
            'register' => 'backoffice.tools.register',
        ];

        $toolName = $slugMap[$toolSlug] ?? null;

        abort_unless($toolName && isset($viewMap[$toolSlug]), 404);

        $catalog = $this->toolCatalog();
        $meta = $catalog[$toolName];
        $setting = Schema::hasTable('tool_settings')
            ? ToolSetting::query()->where('tool_name', $toolName)->first()
            : null;

        $tool = [
            'tool_name' => $toolName,
            'display_name' => (string) ($setting->display_name ?? $meta['display_name']),
            'description' => (string) ($setting->description ?? $meta['description']),
            'is_enabled' => (bool) ($setting->is_enabled ?? true),
        ];

        return view($viewMap[$toolSlug], [
            'tool' => $tool,
            'boActive' => 'ai-agent',
            'currentTool' => $toolName,
        ]);
    }

    public function updateTool(Request $request, string $toolSlug): RedirectResponse
    {
        $slugMap = [
            'reset-password' => 'resetPassword',
            'check-suspend' => 'checkSuspend',
            'register' => 'register',
        ];

        $toolName = $slugMap[$toolSlug] ?? null;
        $catalog = $this->toolCatalog();

        abort_unless($toolName && array_key_exists($toolName, $catalog), 404);

        if (!Schema::hasTable('tool_settings')) {
            return back()->with('error', 'Table tool_settings belum ada. Jalankan migration terlebih dahulu.');
        }

        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        ToolSetting::query()->updateOrCreate(
            ['tool_name' => $toolName],
            [
                'display_name' => trim((string) $data['display_name']),
                'description' => trim((string) ($data['description'] ?? '')),
                'is_enabled' => $request->boolean('is_enabled'),
            ]
        );

        return back()->with('success', ucfirst($toolName) . ' setting berhasil disimpan.');
    }

    private function toolCatalog(): array
    {
        return [
            'resetPassword' => [
                'display_name' => 'Reset Password Tool',
                'description' => 'Tool untuk verifikasi data rekening dan reset password player.',
            ],
            'checkSuspend' => [
                'display_name' => 'Check Suspend Tool',
                'description' => 'Tool untuk cek status suspend akun player.',
            ],
            'register' => [
                'display_name' => 'Register Tool',
                'description' => 'Tool untuk registrasi akun player baru.',
            ],
        ];
    }
}

