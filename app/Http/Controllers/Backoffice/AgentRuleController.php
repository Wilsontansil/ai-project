<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\AgentRule;
use App\Models\ChatAgent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentRuleController extends Controller
{
    public function create(ChatAgent $chatAgent): View
    {
        return view('backoffice.agent-rules.create', [
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
            'type' => ['required', 'in:guideline,forbidden'],
            'category' => ['required', 'string', 'max:50'],
            'level' => ['required', 'in:info,warning,danger'],
            'priority' => ['required', 'integer', 'min:1', 'max:9999'],
        ]);

        $chatAgent->agentRules()->create([
            'title' => trim($data['title']),
            'instruction' => trim($data['instruction']),
            'type' => $data['type'],
            'category' => trim($data['category']),
            'level' => $data['level'],
            'priority' => $data['priority'],
            'is_active' => true,
        ]);

        return redirect()->route('backoffice.chat-agents.edit', $chatAgent)
            ->with('success', __('backoffice.agent_rules.created'));
    }

    public function edit(ChatAgent $chatAgent, AgentRule $agentRule): View
    {
        return view('backoffice.agent-rules.edit', [
            'chatAgent' => $chatAgent,
            'rule' => $agentRule,
            'boActive' => 'chat-agents',
            'currentTool' => null,
        ]);
    }

    public function update(Request $request, ChatAgent $chatAgent, AgentRule $agentRule): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'instruction' => ['required', 'string', 'max:1000'],
            'type' => ['required', 'in:guideline,forbidden'],
            'category' => ['required', 'string', 'max:50'],
            'level' => ['required', 'in:info,warning,danger'],
            'priority' => ['required', 'integer', 'min:1', 'max:9999'],
            'is_active' => ['nullable'],
        ]);

        $agentRule->update([
            'title' => trim($data['title']),
            'instruction' => trim($data['instruction']),
            'type' => $data['type'],
            'category' => trim($data['category']),
            'level' => $data['level'],
            'priority' => $data['priority'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('backoffice.chat-agents.edit', $chatAgent)
            ->with('success', __('backoffice.agent_rules.updated'));
    }

    public function destroy(ChatAgent $chatAgent, AgentRule $agentRule): RedirectResponse
    {
        $agentRule->delete();

        return redirect()->route('backoffice.chat-agents.edit', $chatAgent)
            ->with('success', __('backoffice.agent_rules.deleted'));
    }
}
