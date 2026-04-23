<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\ChatAgent;
use App\Models\KnowledgeBase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
            'description' => ['nullable', 'string', 'max:200'],
            'system_prompt' => ['nullable', 'string', 'max:2000'],
            'model' => ['required', 'string', 'max:60'],
            'max_tokens' => ['required', 'integer', 'min:50', 'max:4096'],
            'temperature' => ['required', 'numeric', 'min:0', 'max:2'],
            'message_await_seconds' => ['required', 'integer', 'min:0', 'max:15'],
            'is_enabled' => ['nullable'],
            'is_default' => ['nullable'],
            'escalation_enabled' => ['nullable'],
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['is_enabled'] = $request->boolean('is_enabled');
        $data['is_default'] = $request->boolean('is_default');
        $data['escalation_enabled'] = $request->boolean('escalation_enabled');

        if ($data['is_default']) {
            ChatAgent::query()->where('is_default', true)->update(['is_default' => false]);
        }

        ChatAgent::create($data);

        return redirect()->route('backoffice.chat-agents.index')
            ->with('success', 'Agent berhasil dibuat.');
    }

    public function edit(Request $request, ChatAgent $chatAgent): View
    {
        $activeTab = $request->query('tab');
        if (!in_array($activeTab, ['general', 'rules'], true)) {
            $activeTab = 'general';
        }

        $agentRules = $chatAgent->agentRules()
            ->orderBy('type')
            ->orderBy('priority')
            ->get();

        $knowledgeEntries = $chatAgent->knowledgeBases()
            ->orderByDesc('updated_at')
            ->get();

        return view('backoffice.chat-agents.edit', [
            'agent' => $chatAgent,
            'agentRules' => $agentRules,
            'knowledgeEntries' => $knowledgeEntries,
            'activeTab' => $activeTab,
        ]);
    }

    public function update(Request $request, ChatAgent $chatAgent): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:200'],
            'system_prompt' => ['nullable', 'string', 'max:2000'],
            'model' => ['required', 'string', 'max:60'],
            'max_tokens' => ['required', 'integer', 'min:50', 'max:4096'],
            'temperature' => ['required', 'numeric', 'min:0', 'max:2'],
            'message_await_seconds' => ['required', 'integer', 'min:0', 'max:15'],
            'is_enabled' => ['nullable'],
            'is_default' => ['nullable'],
            'escalation_enabled' => ['nullable'],
        ]);

        $data['is_enabled'] = $request->boolean('is_enabled');
        $data['is_default'] = $request->boolean('is_default');
        $data['escalation_enabled'] = $request->boolean('escalation_enabled');

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

    public function storeKnowledgeBase(Request $request, ChatAgent $chatAgent): RedirectResponse
    {
        $data = $request->validate([
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('knowledge_base', 'title')->where(fn ($query) => $query->where('chat_agent_id', $chatAgent->id)),
            ],
            'content' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'mimes:txt', 'max:2048'],
        ]);

        $content = $data['content'] ?? '';
        $source = 'manual';
        $fileName = null;

        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $file = $request->file('file');
            $content = (string) file_get_contents($file->getRealPath());
            $source = 'file';
            $fileName = $file->getClientOriginalName();
        }

        KnowledgeBase::query()->create([
            'chat_agent_id' => $chatAgent->id,
            'title' => $data['title'],
            'content' => $content,
            'source' => $source,
            'file_name' => $fileName,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('backoffice.chat-agents.edit', ['chatAgent' => $chatAgent, 'tab' => 'general'])
            ->with('success', 'Knowledge base entry created.');
    }

    public function updateKnowledgeBase(Request $request, ChatAgent $chatAgent, KnowledgeBase $knowledgeBase): RedirectResponse
    {
        $this->ensureKnowledgeOwnership($chatAgent, $knowledgeBase);

        $data = $request->validate([
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('knowledge_base', 'title')
                    ->where(fn ($query) => $query->where('chat_agent_id', $chatAgent->id))
                    ->ignore($knowledgeBase->id),
            ],
            'content' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'mimes:txt', 'max:2048'],
        ]);

        $content = $data['content'] ?? $knowledgeBase->content;
        $source = $knowledgeBase->source;
        $fileName = $knowledgeBase->file_name;

        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $file = $request->file('file');
            $content = (string) file_get_contents($file->getRealPath());
            $source = 'file';
            $fileName = $file->getClientOriginalName();
        }

        $knowledgeBase->update([
            'title' => $data['title'],
            'content' => $content,
            'source' => $source,
            'file_name' => $fileName,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('backoffice.chat-agents.edit', ['chatAgent' => $chatAgent, 'tab' => 'general'])
            ->with('success', 'Knowledge base entry updated.');
    }

    public function destroyKnowledgeBase(ChatAgent $chatAgent, KnowledgeBase $knowledgeBase): RedirectResponse
    {
        $this->ensureKnowledgeOwnership($chatAgent, $knowledgeBase);

        $knowledgeBase->delete();

        return redirect()->route('backoffice.chat-agents.edit', ['chatAgent' => $chatAgent, 'tab' => 'general'])
            ->with('success', 'Knowledge base entry deleted.');
    }

    private function ensureKnowledgeOwnership(ChatAgent $chatAgent, KnowledgeBase $knowledgeBase): void
    {
        if ((int) $knowledgeBase->chat_agent_id !== (int) $chatAgent->id) {
            abort(404);
        }
    }
}
