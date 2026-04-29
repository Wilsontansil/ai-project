<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\SystemConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SystemConfigController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'key'           => ['required', 'string', 'max:191', 'unique:system_configs,key'],
            'value'         => ['nullable', 'string'],
            'source'        => ['nullable', 'string', 'in:manual,datamodel'],
            'data_model_id' => ['nullable', 'integer', 'exists:data_models,id'],
            'query_sql'     => ['nullable', 'string'],
            'from_agent'    => ['nullable', 'integer'],
        ]);

        SystemConfig::create([
            'key'           => $data['key'],
            'value'         => $data['value'] ?? null,
            'source'        => $data['source'] ?? 'manual',
            'data_model_id' => $data['data_model_id'] ?? null,
            'query_sql'     => $data['query_sql'] ?? null,
        ]);

        return $this->redirectBack($request);
    }

    public function update(Request $request, SystemConfig $systemConfig): RedirectResponse
    {
        $data = $request->validate([
            'key'           => ['required', 'string', 'max:191', 'unique:system_configs,key,' . $systemConfig->id],
            'value'         => ['nullable', 'string'],
            'source'        => ['nullable', 'string', 'in:manual,datamodel'],
            'data_model_id' => ['nullable', 'integer', 'exists:data_models,id'],
            'query_sql'     => ['nullable', 'string'],
        ]);

        $systemConfig->update([
            'key'           => $data['key'],
            'value'         => $data['value'] ?? null,
            'source'        => $data['source'] ?? 'manual',
            'data_model_id' => $data['data_model_id'] ?? null,
            'query_sql'     => $data['query_sql'] ?? null,
        ]);

        return $this->redirectBack($request);
    }

    public function sync(Request $request, SystemConfig $systemConfig): RedirectResponse
    {
        try {
            $systemConfig->syncFromDatamodel();
            $msg = 'Synced "' . $systemConfig->key . '" successfully.';
        } catch (\RuntimeException $e) {
            $msg = 'Sync failed: ' . $e->getMessage();
            return $this->redirectBack($request)->with('error', $msg);
        } catch (\Throwable $e) {
            $msg = 'Sync error: ' . $e->getMessage();
            return $this->redirectBack($request)->with('error', $msg);
        }

        return $this->redirectBack($request)->with('success', $msg);
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
