<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\AgentCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CaseController extends Controller
{
    public function index(): View
    {
        $cases = Schema::hasTable('agent_cases')
            ? AgentCase::query()->orderByDesc('created_at')->get()
            : collect();

        return view('backoffice.cases.index', [
            'cases' => $cases,
            'boActive' => 'cases',
            'currentTool' => null,
        ]);
    }

    public function create(): View
    {
        return view('backoffice.cases.create', [
            'boActive' => 'cases',
            'currentTool' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'instruction' => ['required', 'string', 'max:1000'],
            'level' => ['required', 'in:info,warning,danger'],
        ]);

        AgentCase::create([
            'title' => trim($data['title']),
            'instruction' => trim($data['instruction']),
            'level' => $data['level'],
            'is_active' => true,
        ]);

        return redirect()->route('backoffice.cases.index')
            ->with('success', 'Case berhasil ditambahkan. Agent behaviour telah diupdate.');
    }

    public function edit(AgentCase $case): View
    {
        return view('backoffice.cases.edit', [
            'case' => $case,
            'boActive' => 'cases',
            'currentTool' => null,
        ]);
    }

    public function update(Request $request, AgentCase $case): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'instruction' => ['required', 'string', 'max:1000'],
            'level' => ['required', 'in:info,warning,danger'],
            'is_active' => ['nullable'],
        ]);

        $case->update([
            'title' => trim($data['title']),
            'instruction' => trim($data['instruction']),
            'level' => $data['level'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('backoffice.cases.index')
            ->with('success', 'Case berhasil diupdate. Agent behaviour telah disesuaikan.');
    }

    public function destroy(AgentCase $case): RedirectResponse
    {
        $case->delete();

        return redirect()->route('backoffice.cases.index')
            ->with('success', 'Case berhasil dihapus.');
    }
}
