<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\ChatAgent;
use App\Models\DataModel;
use App\Models\KnowledgeBase;
use App\Models\SystemConfig;
use App\Models\Tool;
use App\Services\AI\KnowledgeBaseQueryGuard;
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

        return redirect()->route('backoffice.chat-agents.index')
            ->with('success', 'Agent berhasil dibuat.');
    }

    public function edit(Request $request, ChatAgent $chatAgent): View
    {
        $activeTab = $request->query('tab');
        if (!in_array($activeTab, ['general', 'knowledge-base', 'rules', 'tools', 'system-config'], true)) {
            $activeTab = 'general';
        }

        $systemConfigSearch = trim((string) $request->query('sc_search', ''));

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

        $systemConfigs = SystemConfig::query()
            ->when($systemConfigSearch !== '', function ($query) use ($systemConfigSearch) {
                $query->where(function ($inner) use ($systemConfigSearch) {
                    $inner->where('key', 'like', '%' . $systemConfigSearch . '%')
                        ->orWhere('value', 'like', '%' . $systemConfigSearch . '%')
                        ->orWhere('description', 'like', '%' . $systemConfigSearch . '%')
                        ->orWhere('lookup_field', 'like', '%' . $systemConfigSearch . '%')
                        ->orWhere('lookup_value', 'like', '%' . $systemConfigSearch . '%')
                        ->orWhere('result_field', 'like', '%' . $systemConfigSearch . '%');
                });
            })
            ->orderBy('key')
            ->paginate(15)
            ->appends([
                'tab' => $activeTab,
                'mode' => $knowledgeMode,
                'kb' => $selectedKnowledgeId > 0 ? $selectedKnowledgeId : null,
                'sc_search' => $systemConfigSearch !== '' ? $systemConfigSearch : null,
            ]);

        return view('backoffice.chat-agents.edit', [
            'agent' => $chatAgent,
            'agentRules' => $agentRules,
            'knowledgeEntries' => $knowledgeEntries,
            'selectedKnowledge' => $selectedKnowledge,
            'knowledgeMode' => $knowledgeMode,
            'activeTab' => $activeTab,
            'timezoneOptions' => $this->timezoneOptions(),
            'dataModels' => DataModel::query()->orderBy('model_name')->get(['id', 'model_name', 'slug']),
            'availableTools' => Tool::query()
                ->where('tool_name', '!=', '_bot_config')
                ->orderBy('category')
                ->orderBy('display_name')
                ->get(),
            'systemConfigs' => $systemConfigs,
            'systemConfigSearch' => $systemConfigSearch,
            'systemConfigDataModels' => DataModel::query()
                ->orderBy('model_name')
                ->get(['id', 'model_name', 'table_name', 'connection_name', 'fields']),
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
        ]);

        $data['is_enabled'] = $request->boolean('is_enabled');
        $data['is_default'] = $request->boolean('is_default');
        $data['stop_ai_after_handoff'] = $request->boolean('stop_ai_after_handoff');
        $data['silent_handoff'] = $request->boolean('silent_handoff');

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
        $sourceType = $request->input('source_type', 'manual');

        if ($sourceType === 'datamodel') {
            $data = $request->validate([
                'title' => [
                    'required', 'string', 'max:255',
                    Rule::unique('knowledge_base', 'title')->where(fn ($q) => $q->where('chat_agent_id', $chatAgent->id)),
                ],
                'data_model_id' => ['required', 'exists:data_models,id'],
                'query_sql'     => ['required', 'string'],
            ]);

            $querySql  = trim($data['query_sql']);
            $dataModel = DataModel::findOrFail((int) $data['data_model_id']);
            try {
                KnowledgeBaseQueryGuard::validateSql($querySql);
                $rowCount = KnowledgeBaseQueryGuard::countRows($dataModel->connection_name ?: 'mysqlgame', $querySql);
                if ($rowCount > KnowledgeBaseQueryGuard::MAX_ROWS) {
                    return back()->withInput()->withErrors(['query_sql' => "Query mengembalikan {$rowCount} baris (maks " . KnowledgeBaseQueryGuard::MAX_ROWS . "). Tambahkan klausa WHERE atau LIMIT."]);
                }
            } catch (\InvalidArgumentException $e) {
                return back()->withInput()->withErrors(['query_sql' => $e->getMessage()]);
            } catch (\Throwable $e) {
                return back()->withInput()->withErrors(['query_sql' => 'Query gagal dieksekusi: ' . $e->getMessage()]);
            }

            KnowledgeBase::query()->create([
                'chat_agent_id' => $chatAgent->id,
                'title'         => $data['title'],
                'content'       => null,
                'source'        => 'datamodel',
                'data_model_id' => $data['data_model_id'],
                'query_sql'     => $querySql,
                'is_active'     => $request->boolean('is_active', true),
            ]);
        } else {
            $data = $request->validate([
                'title' => [
                    'required', 'string', 'max:255',
                    Rule::unique('knowledge_base', 'title')->where(fn ($q) => $q->where('chat_agent_id', $chatAgent->id)),
                ],
                'content' => ['nullable', 'string'],
                'file'    => ['nullable', 'file', 'mimes:txt', 'max:2048'],
            ]);

            $content  = $data['content'] ?? '';
            $source   = 'manual';
            $fileName = null;

            if ($request->hasFile('file') && $request->file('file')->isValid()) {
                $file     = $request->file('file');
                $content  = (string) file_get_contents($file->getRealPath());
                $source   = 'file';
                $fileName = $file->getClientOriginalName();
            }

            KnowledgeBase::query()->create([
                'chat_agent_id' => $chatAgent->id,
                'title'         => $data['title'],
                'content'       => $content,
                'source'        => $source,
                'file_name'     => $fileName,
                'is_active'     => $request->boolean('is_active', true),
            ]);
        }

        return redirect()->route('backoffice.chat-agents.edit', ['chatAgent' => $chatAgent, 'tab' => 'knowledge-base'])
            ->with('success', 'Knowledge base entry created.');
    }

    public function updateKnowledgeBase(Request $request, ChatAgent $chatAgent, KnowledgeBase $knowledgeBase): RedirectResponse
    {
        $this->ensureKnowledgeOwnership($chatAgent, $knowledgeBase);

        $sourceType = $request->input('source_type', $knowledgeBase->source);

        if ($sourceType === 'datamodel') {
            $data = $request->validate([
                'title' => [
                    'required', 'string', 'max:255',
                    Rule::unique('knowledge_base', 'title')
                        ->where(fn ($q) => $q->where('chat_agent_id', $chatAgent->id))
                        ->ignore($knowledgeBase->id),
                ],
                'data_model_id' => ['required', 'exists:data_models,id'],
                'query_sql'     => ['required', 'string'],
            ]);

            $querySql  = trim($data['query_sql']);
            $dataModel = DataModel::findOrFail((int) $data['data_model_id']);
            try {
                KnowledgeBaseQueryGuard::validateSql($querySql);
                $rowCount = KnowledgeBaseQueryGuard::countRows($dataModel->connection_name ?: 'mysqlgame', $querySql);
                if ($rowCount > KnowledgeBaseQueryGuard::MAX_ROWS) {
                    return back()->withInput()->withErrors(['query_sql' => "Query mengembalikan {$rowCount} baris (maks " . KnowledgeBaseQueryGuard::MAX_ROWS . "). Tambahkan klausa WHERE atau LIMIT."]);
                }
            } catch (\InvalidArgumentException $e) {
                return back()->withInput()->withErrors(['query_sql' => $e->getMessage()]);
            } catch (\Throwable $e) {
                return back()->withInput()->withErrors(['query_sql' => 'Query gagal dieksekusi: ' . $e->getMessage()]);
            }

            $knowledgeBase->update([
                'title'         => $data['title'],
                'content'       => null,
                'source'        => 'datamodel',
                'file_name'     => null,
                'data_model_id' => $data['data_model_id'],
                'query_sql'     => $querySql,
                'is_active'     => $request->boolean('is_active'),
            ]);
        } else {
            $data = $request->validate([
                'title' => [
                    'required', 'string', 'max:255',
                    Rule::unique('knowledge_base', 'title')
                        ->where(fn ($q) => $q->where('chat_agent_id', $chatAgent->id))
                        ->ignore($knowledgeBase->id),
                ],
                'content' => ['nullable', 'string'],
                'file'    => ['nullable', 'file', 'mimes:txt', 'max:2048'],
            ]);

            $content  = $data['content'] ?? $knowledgeBase->content;
            $source   = $knowledgeBase->source === 'datamodel' ? 'manual' : $knowledgeBase->source;
            $fileName = $knowledgeBase->file_name;

            if ($request->hasFile('file') && $request->file('file')->isValid()) {
                $file     = $request->file('file');
                $content  = (string) file_get_contents($file->getRealPath());
                $source   = 'file';
                $fileName = $file->getClientOriginalName();
            }

            $knowledgeBase->update([
                'title'         => $data['title'],
                'content'       => $content,
                'source'        => $source,
                'file_name'     => $fileName,
                'data_model_id' => null,
                'query_sql'     => null,
                'is_active'     => $request->boolean('is_active'),
            ]);
        }

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

}
