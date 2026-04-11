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
            'tool_name' => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z0-9_-]+$/', 'unique:tools,tool_name'],
            'display_name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'params' => ['nullable', 'array'],
            'params.*.name' => ['required_with:params', 'string', 'max:80'],
            'params.*.description' => ['nullable', 'string', 'max:255'],
            'keywords' => ['nullable', 'string'],
            'missing_message' => ['nullable', 'string', 'max:1000'],
            'information_text' => ['nullable', 'string', 'max:2000'],
        ]);

        $parameters = $this->buildParametersFromInput($request->input('params', []));

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
            'params' => ['nullable', 'array'],
            'params.*.name' => ['required_with:params', 'string', 'max:80'],
            'params.*.description' => ['nullable', 'string', 'max:255'],
            'keywords' => ['nullable', 'string'],
            'missing_message' => ['nullable', 'string', 'max:1000'],
            'information_text' => ['nullable', 'string', 'max:2000'],
        ]);

        $parameters = $this->buildParametersFromInput($request->input('params', []));

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

    /**
     * Build OpenAI-compatible parameters JSON from simple form input.
     */
    private function buildParametersFromInput(?array $params): ?array
    {
        if (empty($params)) {
            return null;
        }

        $properties = [];
        $required = [];

        foreach ($params as $param) {
            $name = trim((string) ($param['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $properties[$name] = [
                'type' => 'string',
                'description' => trim((string) ($param['description'] ?? '')),
            ];

            if (!empty($param['required'])) {
                $required[] = $name;
            }
        }

        if ($properties === []) {
            return null;
        }

        return [
            'type' => 'object',
            'properties' => $properties,
            'required' => $required,
        ];
    }
}
