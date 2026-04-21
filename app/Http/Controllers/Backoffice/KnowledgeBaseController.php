<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeBase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeBaseController extends Controller
{
    public function index(): View
    {
        $entries = KnowledgeBase::query()->orderByDesc('created_at')->paginate(20);

        return view('backoffice.knowledge-base.index', [
            'entries'  => $entries,
            'boActive' => 'knowledge-base',
        ]);
    }

    public function create(): View
    {
        return view('backoffice.knowledge-base.create', [
            'boActive' => 'knowledge-base',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'   => ['required', 'string', 'max:255'],
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
            'title'     => $data['title'],
            'content'   => $content,
            'source'    => $source,
            'file_name' => $fileName,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('backoffice.knowledge-base.index')
            ->with('success', 'Knowledge base entry created.');
    }

    public function edit(KnowledgeBase $knowledgeBase): View
    {
        return view('backoffice.knowledge-base.edit', [
            'entry'    => $knowledgeBase,
            'boActive' => 'knowledge-base',
        ]);
    }

    public function update(Request $request, KnowledgeBase $knowledgeBase): RedirectResponse
    {
        $data = $request->validate([
            'title'   => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'file'    => ['nullable', 'file', 'mimes:txt', 'max:2048'],
        ]);

        $content  = $data['content'] ?? $knowledgeBase->content;
        $source   = $knowledgeBase->source;
        $fileName = $knowledgeBase->file_name;

        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $file     = $request->file('file');
            $content  = (string) file_get_contents($file->getRealPath());
            $source   = 'file';
            $fileName = $file->getClientOriginalName();
        }

        $knowledgeBase->update([
            'title'     => $data['title'],
            'content'   => $content,
            'source'    => $source,
            'file_name' => $fileName,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('backoffice.knowledge-base.index')
            ->with('success', 'Knowledge base entry updated.');
    }

    public function destroy(KnowledgeBase $knowledgeBase): RedirectResponse
    {
        $knowledgeBase->delete();

        return redirect()->route('backoffice.knowledge-base.index')
            ->with('success', 'Knowledge base entry deleted.');
    }
}
