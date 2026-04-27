<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\ChatAgent;
use App\Models\KnowledgeBase;
use App\Models\Tool;
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
        return view('backoffice.chat-agents.create', [
            'timezoneOptions' => $this->timezoneOptions(),
        ]);
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
            'timezone' => ['required', 'string', 'timezone'],
            'is_enabled' => ['nullable'],
            'is_default' => ['nullable'],
            'escalation_condition' => ['nullable', 'string', 'max:3000'],
            'stop_ai_after_handoff' => ['nullable'],
            'silent_handoff' => ['nullable'],
            'tool_ids' => ['nullable', 'array'],
            'tool_ids.*' => ['integer', 'exists:tools,id'],
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['is_enabled'] = $request->boolean('is_enabled');
        $data['is_default'] = $request->boolean('is_default');
        $data['stop_ai_after_handoff'] = $request->boolean('stop_ai_after_handoff');
        $data['silent_handoff'] = $request->boolean('silent_handoff');

        if ($data['is_default']) {
            ChatAgent::query()->where('is_default', true)->update(['is_default' => false]);
        }

        $agent = ChatAgent::create($data);

        $toolIds = $request->has('tool_ids')
            ? (array) $request->input('tool_ids', [])
            : Tool::query()
                ->where('tool_name', '!=', '_bot_config')
                ->where('is_enabled', true)
                ->pluck('id')
                ->all();

        $this->syncAgentTools($agent, $toolIds);

        return redirect()->route('backoffice.chat-agents.index')
            ->with('success', 'Agent berhasil dibuat.');
    }

    public function edit(Request $request, ChatAgent $chatAgent): View
    {
        $chatAgent->loadMissing('tools:id');

        $activeTab = $request->query('tab');
        if (!in_array($activeTab, ['general', 'knowledge-base', 'rules'], true)) {
            $activeTab = 'general';
        }

        $knowledgeMode = $request->query('mode');
        if (!in_array($knowledgeMode, ['view', 'edit'], true)) {
            $knowledgeMode = 'view';
        }

        $selectedKnowledgeId = (int) $request->query('kb', 0);

        $agentRules = $chatAgent->agentRules()
            ->orderBy('type')
            ->orderBy('priority')
            ->get();

        $knowledgeEntries = $chatAgent->knowledgeBases()
            ->orderByDesc('updated_at')
            ->get();

        $selectedKnowledge = null;
        if ($selectedKnowledgeId > 0) {
            $selectedKnowledge = $knowledgeEntries->firstWhere('id', $selectedKnowledgeId);
        }

        return view('backoffice.chat-agents.edit', [
            'agent' => $chatAgent,
            'agentRules' => $agentRules,
            'knowledgeEntries' => $knowledgeEntries,
            'selectedKnowledge' => $selectedKnowledge,
            'knowledgeMode' => $knowledgeMode,
            'activeTab' => $activeTab,
            'timezoneOptions' => $this->timezoneOptions(),
            'availableTools' => Tool::query()
                ->where('tool_name', '!=', '_bot_config')
                ->orderBy('category')
                ->orderBy('display_name')
                ->get(['id', 'display_name', 'tool_name', 'category', 'is_enabled']),
            'selectedToolIds' => $chatAgent->tools->pluck('id')->all(),
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
            'timezone' => ['required', 'string', 'timezone'],
            'is_enabled' => ['nullable'],
            'is_default' => ['nullable'],
            'escalation_condition' => ['nullable', 'string', 'max:3000'],
            'stop_ai_after_handoff' => ['nullable'],
            'silent_handoff' => ['nullable'],
            'tool_ids' => ['nullable', 'array'],
            'tool_ids.*' => ['integer', 'exists:tools,id'],
        ]);

        $data['is_enabled'] = $request->boolean('is_enabled');
        $data['is_default'] = $request->boolean('is_default');
        $data['stop_ai_after_handoff'] = $request->boolean('stop_ai_after_handoff');
        $data['silent_handoff'] = $request->boolean('silent_handoff');

        if ($data['is_default'] && !$chatAgent->is_default) {
            ChatAgent::query()->where('is_default', true)->update(['is_default' => false]);
        }

        $chatAgent->update($data);

        $this->syncAgentTools($chatAgent, $request->input('tool_ids', []));

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

        return redirect()->route('backoffice.chat-agents.edit', ['chatAgent' => $chatAgent, 'tab' => 'knowledge-base'])
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

        return redirect()->route('backoffice.chat-agents.edit', ['chatAgent' => $chatAgent, 'tab' => 'knowledge-base'])
            ->with('success', 'Knowledge base entry updated.');
    }

    public function destroyKnowledgeBase(ChatAgent $chatAgent, KnowledgeBase $knowledgeBase): RedirectResponse
    {
        $this->ensureKnowledgeOwnership($chatAgent, $knowledgeBase);

        $knowledgeBase->delete();

        return redirect()->route('backoffice.chat-agents.edit', ['chatAgent' => $chatAgent, 'tab' => 'knowledge-base'])
            ->with('success', 'Knowledge base entry deleted.');
    }

    private function ensureKnowledgeOwnership(ChatAgent $chatAgent, KnowledgeBase $knowledgeBase): void
    {
        if ((int) $knowledgeBase->chat_agent_id !== (int) $chatAgent->id) {
            abort(404);
        }
    }

    /**
     * @return array<int, string>
     */
    private function timezoneOptions(): array
    {
        return \DateTimeZone::listIdentifiers();
    }

    /**
     * @param array<int, mixed> $toolIds
     */
    private function syncAgentTools(ChatAgent $agent, array $toolIds): void
    {
        $normalizedIds = collect($toolIds)
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn ($id) => $id > 0)
            ->values();

        $validIds = Tool::query()
            ->whereIn('id', $normalizedIds)
            ->where('tool_name', '!=', '_bot_config')
            ->pluck('id')
            ->all();

        $agent->tools()->sync($validIds);
    }
}
