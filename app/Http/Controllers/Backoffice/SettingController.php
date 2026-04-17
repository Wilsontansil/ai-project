<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\ProjectSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $settings = Schema::hasTable('project_settings')
            ? ProjectSetting::query()->orderByRaw("FIELD(`group`, 'webhook', 'openai', 'telegram', 'livechat', 'whatsapp', 'agent', 'retention', 'support')")->get()
            : collect();

        $grouped = $settings->groupBy('group');

        return view('backoffice.settings.index', [
            'grouped' => $grouped,
            'boActive' => 'settings',
            'currentTool' => null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $settings = ProjectSetting::all();

        $rules = [];

        foreach ($settings as $setting) {
            $inputKey = 'setting_' . $setting->id;

            if (in_array($setting->key, ['conversation_retention_days', 'customer_memory_retention_days'], true)) {
                $rules[$inputKey] = ['nullable', 'integer', 'min:1', 'max:3650'];
            }
        }

        if ($rules !== []) {
            $request->validate($rules);
        }

        foreach ($settings as $setting) {
            $inputKey = 'setting_' . $setting->id;
            $newValue = $request->input($inputKey);

            // For secret fields, skip if left empty (keeps old value)
            if ($setting->type === 'secret' && ($newValue === null || $newValue === '')) {
                continue;
            }

            $setting->update(['value' => $newValue]);
        }

        ProjectSetting::clearCache();

        return back()->with('success', 'Settings berhasil disimpan.');
    }
}
