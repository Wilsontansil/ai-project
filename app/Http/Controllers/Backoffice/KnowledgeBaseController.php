<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\AILearnedMemory;
use App\Models\KnowledgeBase;
use App\Services\FileParserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeBaseController extends Controller
{
    // ─── Knowledge Base CRUD ─────────────────────────

    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'knowledge');
        $search = trim((string) $request->query('search', ''));

        $knowledgeQuery = KnowledgeBase::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where('question', 'like', '%' . $search . '%')
                    ->orWhere('answer', 'like', '%' . $search . '%')
                    ->orWhere('category', 'like', '%' . $search . '%');
            })
            ->latest()
            ->paginate(20, ['*'], 'kb_page')
            ->withQueryString();

        $memoriesQuery = AILearnedMemory::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where('pattern', 'like', '%' . $search . '%')
                    ->orWhere('learned_response', 'like', '%' . $search . '%');
            })
            ->latest()
            ->paginate(20, ['*'], 'mem_page')
            ->withQueryString();

        return view('backoffice.knowledge.index', [
            'entries' => $knowledgeQuery,
            'memories' => $memoriesQuery,
            'tab' => $tab,
            'search' => $search,
            'stats' => [
                'total_knowledge' => KnowledgeBase::query()->count(),
                'active_knowledge' => KnowledgeBase::query()->where('is_active', true)->count(),
                'total_memories' => AILearnedMemory::query()->count(),
                'approved_memories' => AILearnedMemory::query()->where('is_approved', true)->count(),
            ],
            'boActive' => 'knowledge',
        ]);
    }

    public function create(): View
    {
        return view('backoffice.knowledge.create', [
            'boActive' => 'knowledge',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'category' => ['nullable', 'string', 'max:100'],
            'question' => ['required', 'string', 'max:1000'],
            'answer' => ['required', 'string', 'max:10000'],
            'tags' => ['nullable', 'string'],
            'confidence_score' => ['nullable', 'numeric', 'min:0', 'max:1'],
        ]);

        KnowledgeBase::create([
            'category' => $request->input('category'),
            'question' => $request->input('question'),
            'answer' => $request->input('answer'),
            'tags' => $request->input('tags') ? array_map('trim', explode(',', $request->input('tags'))) : null,
            'confidence_score' => $request->input('confidence_score', 0.7),
            'source' => 'manual',
            'is_active' => true,
        ]);

        return redirect()->route('backoffice.knowledge.index')
            ->with('success', 'Knowledge berhasil ditambahkan.');
    }

    public function edit(KnowledgeBase $knowledge): View
    {
        return view('backoffice.knowledge.edit', [
            'entry' => $knowledge,
            'boActive' => 'knowledge',
        ]);
    }

    public function update(Request $request, KnowledgeBase $knowledge): RedirectResponse
    {
        $request->validate([
            'category' => ['nullable', 'string', 'max:100'],
            'question' => ['required', 'string', 'max:1000'],
            'answer' => ['required', 'string', 'max:10000'],
            'tags' => ['nullable', 'string'],
            'confidence_score' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'is_active' => ['nullable'],
        ]);

        $knowledge->update([
            'category' => $request->input('category'),
            'question' => $request->input('question'),
            'answer' => $request->input('answer'),
            'tags' => $request->input('tags') ? array_map('trim', explode(',', $request->input('tags'))) : null,
            'confidence_score' => $request->input('confidence_score', 0.7),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('backoffice.knowledge.index')
            ->with('success', 'Knowledge berhasil diupdate.');
    }

    public function destroy(KnowledgeBase $knowledge): RedirectResponse
    {
        $name = $knowledge->question;
        $knowledge->delete();

        return redirect()->route('backoffice.knowledge.index')
            ->with('success', 'Knowledge "' . \Illuminate\Support\Str::limit($name, 50) . '" berhasil dihapus.');
    }

    // ─── File Upload ─────────────────────────────────

    public function uploadForm(): View
    {
        return view('backoffice.knowledge.upload', [
            'boActive' => 'knowledge',
            'supportedExtensions' => FileParserService::supportedExtensions(),
        ]);
    }

    public function upload(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:5120'], // 5MB
            'category' => ['nullable', 'string', 'max:100'],
            'confidence_score' => ['nullable', 'numeric', 'min:0', 'max:1'],
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, FileParserService::supportedExtensions())) {
            return redirect()->back()
                ->withErrors(['file' => 'Format file tidak didukung. Gunakan: ' . implode(', ', FileParserService::supportedExtensions())])
                ->withInput();
        }

        $parser = new FileParserService();
        $entries = $parser->parse($file);

        if ($entries === []) {
            return redirect()->back()
                ->withErrors(['file' => 'Tidak ada data yang bisa diparse dari file ini. Pastikan format sesuai (Q:/A: atau kolom question/answer).'])
                ->withInput();
        }

        $filename = $file->getClientOriginalName();
        $category = $request->input('category');
        $confidence = (float) $request->input('confidence_score', 0.6);
        $imported = 0;

        foreach ($entries as $entry) {
            KnowledgeBase::create([
                'category' => $category,
                'question' => $entry['question'],
                'answer' => $entry['answer'],
                'tags' => null,
                'confidence_score' => $confidence,
                'source' => 'file',
                'source_file' => $filename,
                'is_active' => true,
            ]);
            $imported++;
        }

        return redirect()->route('backoffice.knowledge.index')
            ->with('success', "Berhasil import {$imported} knowledge dari file \"{$filename}\".");
    }

    // ─── Learned Memories Management ─────────────────

    public function approveMemory(AILearnedMemory $memory): RedirectResponse
    {
        $memory->update(['is_approved' => true, 'is_active' => true]);

        return redirect()->back()
            ->with('success', 'Memory di-approve dan aktif.');
    }

    public function rejectMemory(AILearnedMemory $memory): RedirectResponse
    {
        $memory->update(['is_approved' => false, 'is_active' => false]);

        return redirect()->back()
            ->with('success', 'Memory di-reject.');
    }

    public function destroyMemory(AILearnedMemory $memory): RedirectResponse
    {
        $memory->delete();

        return redirect()->back()
            ->with('success', 'Memory berhasil dihapus.');
    }
}
