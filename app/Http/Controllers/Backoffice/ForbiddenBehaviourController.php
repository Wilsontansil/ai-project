<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\ForbiddenBehaviour;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ForbiddenBehaviourController extends Controller
{
    public function index(): View
    {
        $rules = Schema::hasTable('forbidden_behaviours')
            ? ForbiddenBehaviour::query()->orderByRaw("FIELD(level, 'danger', 'warning', 'info')")->get()
            : collect();

        return view('backoffice.forbidden-behaviours.index', [
            'rules' => $rules,
            'boActive' => 'forbidden',
            'currentTool' => null,
        ]);
    }

    public function create(): View
    {
        return view('backoffice.forbidden-behaviours.create', [
            'boActive' => 'forbidden',
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

        ForbiddenBehaviour::create([
            'title' => trim($data['title']),
            'instruction' => trim($data['instruction']),
            'level' => $data['level'],
            'is_active' => true,
        ]);

        return redirect()->route('backoffice.forbidden.index')
            ->with('success', 'Rule berhasil ditambahkan.');
    }

    public function edit(ForbiddenBehaviour $forbidden_behaviour): View
    {
        return view('backoffice.forbidden-behaviours.edit', [
            'rule' => $forbidden_behaviour,
            'boActive' => 'forbidden',
            'currentTool' => null,
        ]);
    }

    public function update(Request $request, ForbiddenBehaviour $forbidden_behaviour): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'instruction' => ['required', 'string', 'max:1000'],
            'level' => ['required', 'in:info,warning,danger'],
            'is_active' => ['nullable'],
        ]);

        $forbidden_behaviour->update([
            'title' => trim($data['title']),
            'instruction' => trim($data['instruction']),
            'level' => $data['level'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('backoffice.forbidden.index')
            ->with('success', 'Rule berhasil diupdate.');
    }

    public function destroy(ForbiddenBehaviour $forbidden_behaviour): RedirectResponse
    {
        $forbidden_behaviour->delete();

        return redirect()->route('backoffice.forbidden.index')
            ->with('success', 'Rule berhasil dihapus.');
    }
}
