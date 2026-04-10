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
            ->where('class_name', '!=', '')
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
            'class_name' => ['required', 'string', 'max:255'],
        ]);

        Tool::create([
            'tool_name' => trim($data['tool_name']),
            'display_name' => trim($data['display_name']),
            'description' => trim($data['description'] ?? ''),
            'class_name' => trim($data['class_name']),
            'slug' => Str::slug($data['tool_name']),
            'is_enabled' => $request->boolean('is_enabled'),
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
            'class_name' => ['required', 'string', 'max:255'],
        ]);

        $tool->update([
            'display_name' => trim($data['display_name']),
            'description' => trim($data['description'] ?? ''),
            'class_name' => trim($data['class_name']),
            'is_enabled' => $request->boolean('is_enabled'),
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
