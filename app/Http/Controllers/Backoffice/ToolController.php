<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Tool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ToolController extends Controller
{
    public function index(): View
    {
        $tools = Tool::query()
            ->where('tool_name', '!=', '_bot_config')
            ->orderBy('id')
            ->get();

        return view('backoffice.tools.index', [
            'tools' => $tools,
            'boActive' => 'tools',
        ]);
    }

    public function create(): View
    {
        return view('backoffice.tools.create', [
            'boActive' => 'tools',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tool_name' => ['required', 'string', 'max:80', 'unique:tools,tool_name'],
            'display_name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'parameters' => ['nullable', 'string'],
            'keywords' => ['nullable', 'string'],
            'missing_message' => ['nullable', 'string', 'max:1000'],
            'information_text' => ['nullable', 'string', 'max:2000'],
        ]);

        $parameters = null;
        if (!empty($data['parameters'])) {
            $parameters = json_decode($data['parameters'], true);
            if (!is_array($parameters)) {
                return back()->withErrors(['parameters' => 'Parameters harus berupa JSON yang valid.'])->withInput();
            }
        }

        $keywords = null;
        if (!empty($data['keywords'])) {
            $keywords = array_map('trim', explode(',', $data['keywords']));
            $keywords = array_values(array_filter($keywords, fn ($k) => $k !== ''));
        }

        Tool::create([
            'tool_name' => trim($data['tool_name']),
            'display_name' => trim($data['display_name']),
            'description' => trim($data['description'] ?? ''),
            'slug' => Str::slug($data['tool_name']),
            'is_enabled' => $request->boolean('is_enabled'),
            'parameters' => $parameters,
            'keywords' => $keywords,
            'missing_message' => trim($data['missing_message'] ?? '') ?: null,
            'information_text' => trim($data['information_text'] ?? '') ?: null,
            'meta' => [
                'icon' => trim($request->input('icon', 'M13 10V3L4 14h7v7l9-11h-7z')),
            ],
        ]);

        return redirect()->route('backoffice.tools.index')->with('success', 'Tool berhasil ditambahkan.');
    }

    public function edit(Tool $tool): View
    {
        return view('backoffice.tools.edit', [
            'tool' => $tool,
            'boActive' => 'tools',
        ]);
    }

    public function update(Request $request, Tool $tool): RedirectResponse
    {
        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'parameters' => ['nullable', 'string'],
            'keywords' => ['nullable', 'string'],
            'missing_message' => ['nullable', 'string', 'max:1000'],
            'information_text' => ['nullable', 'string', 'max:2000'],
        ]);

        $parameters = $tool->parameters;
        if ($request->has('parameters')) {
            $raw = $data['parameters'] ?? '';
            if ($raw !== '') {
                $decoded = json_decode($raw, true);
                if (!is_array($decoded)) {
                    return back()->withErrors(['parameters' => 'Parameters harus berupa JSON yang valid.'])->withInput();
                }
                $parameters = $decoded;
            } else {
                $parameters = null;
            }
        }

        $keywords = $tool->keywords;
        if ($request->has('keywords')) {
            $raw = trim($data['keywords'] ?? '');
            if ($raw !== '') {
                $keywords = array_map('trim', explode(',', $raw));
                $keywords = array_values(array_filter($keywords, fn ($k) => $k !== ''));
            } else {
                $keywords = null;
            }
        }

        $tool->update([
            'display_name' => trim($data['display_name']),
            'description' => trim($data['description'] ?? ''),
            'is_enabled' => $request->boolean('is_enabled'),
            'parameters' => $parameters,
            'keywords' => $keywords,
            'missing_message' => trim($data['missing_message'] ?? '') ?: null,
            'information_text' => trim($data['information_text'] ?? '') ?: null,
            'meta' => array_merge($tool->meta ?? [], [
                'icon' => trim($request->input('icon', $tool->meta['icon'] ?? 'M13 10V3L4 14h7v7l9-11h-7z')),
            ]),
        ]);

        return redirect()->route('backoffice.tools.index')->with('success', $tool->display_name . ' berhasil diperbarui.');
    }

    public function destroy(Tool $tool): RedirectResponse
    {
        $name = $tool->display_name;
        $tool->delete();

        return redirect()->route('backoffice.tools.index')->with('success', $name . ' berhasil dihapus.');
    }
}
