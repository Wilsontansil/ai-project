<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\ToolSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AIAgentController extends Controller
{
    public function index(): View
    {
        $catalog = $this->toolCatalog();
        $settings = collect();

        if (Schema::hasTable('tool_settings')) {
            $settings = ToolSetting::query()->get()->keyBy('tool_name');
        }

        $tools = [];
        foreach ($catalog as $toolName => $meta) {
            $current = $settings->get($toolName);

            $tools[] = [
                'tool_name' => $toolName,
                'display_name' => (string) ($current->display_name ?? $meta['display_name']),
                'description' => (string) ($current->description ?? $meta['description']),
                'is_enabled' => (bool) ($current->is_enabled ?? true),
            ];
        }

        return view('backoffice.ai-agent', [
            'tools' => $tools,
            'hasToolSettingsTable' => Schema::hasTable('tool_settings'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        if (!Schema::hasTable('tool_settings')) {
            return back()->with('error', 'Table tool_settings belum ada. Jalankan migration terlebih dahulu.');
        }

        $payload = $request->validate([
            'tools' => ['required', 'array'],
            'tools.*.tool_name' => ['required', 'string'],
            'tools.*.display_name' => ['required', 'string', 'max:120'],
            'tools.*.description' => ['nullable', 'string', 'max:500'],
        ]);

        $enabledMap = (array) $request->input('enabled', []);
        $catalog = $this->toolCatalog();

        foreach ($payload['tools'] as $item) {
            $toolName = (string) $item['tool_name'];

            if (!array_key_exists($toolName, $catalog)) {
                continue;
            }

            ToolSetting::query()->updateOrCreate(
                ['tool_name' => $toolName],
                [
                    'display_name' => trim((string) $item['display_name']),
                    'description' => trim((string) ($item['description'] ?? '')),
                    'is_enabled' => isset($enabledMap[$toolName]) && $enabledMap[$toolName] === '1',
                ]
            );
        }

        return back()->with('success', 'AI Agent tools setting berhasil disimpan.');
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
        ];
    }
}

