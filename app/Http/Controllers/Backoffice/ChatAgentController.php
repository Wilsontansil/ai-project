<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\ChatAgent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ChatAgentController extends Controller
{
    public function index(): View
    {
        $agents = ChatAgent::query()->orderByDesc('is_default')->orderBy('name')->get();

        return view('backoffice.chat-agents.index', compact('agents'));
    }

    public function create(): View
    {
        return view('backoffice.chat-agents.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'system_prompt' => ['nullable', 'string'],
            'model' => ['required', 'string', 'max:60'],
            'max_tokens' => ['required', 'integer', 'min:50', 'max:4096'],
            'temperature' => ['required', 'numeric', 'min:0', 'max:2'],
            'is_enabled' => ['nullable'],
            'is_default' => ['nullable'],
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['is_enabled'] = $request->boolean('is_enabled');
        $data['is_default'] = $request->boolean('is_default');

        if ($data['is_default']) {
            ChatAgent::query()->where('is_default', true)->update(['is_default' => false]);
        }

        ChatAgent::create($data);

        return redirect()->route('backoffice.chat-agents.index')
            ->with('success', 'Agent berhasil dibuat.');
    }

    public function edit(ChatAgent $chatAgent): View
    {
        $forbiddenRules = $chatAgent->forbiddenBehaviours()
            ->orderByRaw("FIELD(level, 'danger', 'warning', 'info')")
            ->get();

        return view('backoffice.chat-agents.edit', [
            'agent' => $chatAgent,
            'forbiddenRules' => $forbiddenRules,
        ]);
    }

    public function update(Request $request, ChatAgent $chatAgent): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'system_prompt' => ['nullable', 'string'],
            'model' => ['required', 'string', 'max:60'],
            'max_tokens' => ['required', 'integer', 'min:50', 'max:4096'],
            'temperature' => ['required', 'numeric', 'min:0', 'max:2'],
            'is_enabled' => ['nullable'],
            'is_default' => ['nullable'],
        ]);

        $data['is_enabled'] = $request->boolean('is_enabled');
        $data['is_default'] = $request->boolean('is_default');

        if ($data['is_default'] && !$chatAgent->is_default) {
            ChatAgent::query()->where('is_default', true)->update(['is_default' => false]);
        }

        $chatAgent->update($data);

        return back()->with('success', 'Agent berhasil diperbarui.');
    }

    public function destroy(ChatAgent $chatAgent): RedirectResponse
    {
        $name = $chatAgent->name;
        $chatAgent->delete();

        return redirect()->route('backoffice.chat-agents.index')
            ->with('success', "Agent \"{$name}\" berhasil dihapus.");
    }

    public function duplicate(ChatAgent $chatAgent): RedirectResponse
    {
        $clone = $chatAgent->replicate();
        $clone->name = $chatAgent->name . ' (Copy)';
        $clone->slug = Str::slug($clone->name) . '-' . Str::random(4);
        $clone->is_default = false;
        $clone->save();

        return redirect()->route('backoffice.chat-agents.index')
            ->with('success', "Agent \"{$chatAgent->name}\" berhasil diduplikasi.");
    }
}
