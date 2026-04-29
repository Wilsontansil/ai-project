<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\SystemConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SystemConfigController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'key'      => ['required', 'string', 'max:191', 'unique:system_configs,key'],
            'value'    => ['nullable', 'string'],
            'description' => ['nullable', 'string', 'max:1000'],
            'from_agent' => ['nullable', 'integer'],
        ]);

        SystemConfig::create([
            'key' => $data['key'],
            'value' => $data['value'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        return $this->redirectBack($request);
    }

    public function update(Request $request, SystemConfig $systemConfig): RedirectResponse
    {
        $data = $request->validate([
            'key'   => ['required', 'string', 'max:191', 'unique:system_configs,key,' . $systemConfig->id],
            'value' => ['nullable', 'string'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $systemConfig->update([
            'key' => $data['key'],
            'value' => $data['value'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        return $this->redirectBack($request);
    }

    public function destroy(Request $request, SystemConfig $systemConfig): RedirectResponse
    {
        $systemConfig->delete();

        return $this->redirectBack($request);
    }

    private function redirectBack(Request $request): RedirectResponse
    {
        $agentId = (int) $request->input('from_agent', $request->query('from_agent', 0));

        if ($agentId > 0) {
            return redirect()->route('backoffice.chat-agents.edit', [
                'chatAgent' => $agentId,
                'tab'       => 'system-config',
            ])->with('success', 'System config saved.');
        }

        return redirect()->back()->with('success', 'System config saved.');
    }
}
