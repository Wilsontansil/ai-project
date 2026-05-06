<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\AgentRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentRuleController extends Controller
{
    public function index(): View
    {
        $rules = AgentRule::query()->orderBy('priority')->orderBy('title')->paginate(50);

        return view('backoffice.agent-rules.index', [
            'rules'    => $rules,
            'boActive' => 'agent-rules',
        ]);
    }

    public function create(): View
    {
        return view('backoffice.agent-rules.create', [
            'boActive' => 'agent-rules',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:100'],
            'instruction' => ['required', 'string', 'max:1000'],
            'type'        => ['required', 'in:guideline,forbidden'],
            'category'    => ['required', 'string', 'max:50'],
            'level'       => ['required', 'in:info,warning,danger'],
            'priority'    => ['required', 'integer', 'min:1', 'max:9999'],
        ]);

        AgentRule::query()->create([
            'chat_agent_id' => null,
            'title'         => trim($data['title']),
            'instruction'   => trim($data['instruction']),
            'type'          => $data['type'],
            'category'      => trim($data['category']),
            'level'         => $data['level'],
            'priority'      => $data['priority'],
            'is_active'     => true,
        ]);

        return redirect()->route('backoffice.agent-rules.index')
            ->with('success', __('backoffice.agent_rules.created'));
    }

    public function edit(AgentRule $agentRule): View
    {
        return view('backoffice.agent-rules.edit', [
            'rule'     => $agentRule,
            'boActive' => 'agent-rules',
        ]);
    }

    public function update(Request $request, AgentRule $agentRule): RedirectResponse
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:100'],
            'instruction' => ['required', 'string', 'max:1000'],
            'type'        => ['required', 'in:guideline,forbidden'],
            'category'    => ['required', 'string', 'max:50'],
            'level'       => ['required', 'in:info,warning,danger'],
            'priority'    => ['required', 'integer', 'min:1', 'max:9999'],
            'is_active'   => ['nullable'],
        ]);

        $agentRule->update([
            'title'       => trim($data['title']),
            'instruction' => trim($data['instruction']),
            'type'        => $data['type'],
            'category'    => trim($data['category']),
            'level'       => $data['level'],
            'priority'    => $data['priority'],
            'is_active'   => $request->boolean('is_active'),
        ]);

        return redirect()->route('backoffice.agent-rules.index')
            ->with('success', __('backoffice.agent_rules.updated'));
    }

    public function destroy(AgentRule $agentRule): RedirectResponse
    {
        $agentRule->delete();

        return redirect()->route('backoffice.agent-rules.index')
            ->with('success', __('backoffice.agent_rules.deleted'));
    }
}
