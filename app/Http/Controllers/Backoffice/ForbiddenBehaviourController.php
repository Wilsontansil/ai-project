<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\ChatAgent;
use App\Models\ForbiddenBehaviour;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ForbiddenBehaviourController extends Controller
{
    public function create(ChatAgent $chatAgent): View
    {
        return view('backoffice.forbidden-behaviours.create', [
            'chatAgent' => $chatAgent,
            'boActive' => 'chat-agents',
            'currentTool' => null,
        ]);
    }

    public function store(Request $request, ChatAgent $chatAgent): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'instruction' => ['required', 'string', 'max:1000'],
            'level' => ['required', 'in:info,warning,danger'],
        ]);

        $chatAgent->forbiddenBehaviours()->create([
            'title' => trim($data['title']),
            'instruction' => trim($data['instruction']),
            'level' => $data['level'],
            'is_active' => true,
        ]);

        return redirect()->route('backoffice.chat-agents.edit', $chatAgent)
            ->with('success', 'Rule berhasil ditambahkan.');
    }

    public function edit(ChatAgent $chatAgent, ForbiddenBehaviour $forbidden_behaviour): View
    {
        return view('backoffice.forbidden-behaviours.edit', [
            'chatAgent' => $chatAgent,
            'rule' => $forbidden_behaviour,
            'boActive' => 'chat-agents',
            'currentTool' => null,
        ]);
    }

    public function update(Request $request, ChatAgent $chatAgent, ForbiddenBehaviour $forbidden_behaviour): RedirectResponse
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

        return redirect()->route('backoffice.chat-agents.edit', $chatAgent)
            ->with('success', 'Rule berhasil diupdate.');
    }

    public function destroy(ChatAgent $chatAgent, ForbiddenBehaviour $forbidden_behaviour): RedirectResponse
    {
        $forbidden_behaviour->delete();

        return redirect()->route('backoffice.chat-agents.edit', $chatAgent)
            ->with('success', 'Rule berhasil dihapus.');
    }
}
