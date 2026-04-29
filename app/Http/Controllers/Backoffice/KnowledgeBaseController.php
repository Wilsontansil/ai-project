<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\DataModel;
use App\Models\KnowledgeBase;
use App\Models\SystemConfig;
use App\Services\AI\KnowledgeBaseQueryGuard;
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
        $dataModels = DataModel::query()->orderBy('model_name')->get(['id', 'model_name', 'slug']);

        return view('backoffice.knowledge-base.create', [
            'dataModels'    => $dataModels,
            'systemConfigs' => SystemConfig::orderBy('key')->get(['key', 'value']),
            'boActive'      => 'knowledge-base',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $sourceType = $request->input('source_type', 'manual');

        if ($sourceType === 'datamodel') {
            $data = $request->validate([
                'title'         => ['required', 'string', 'max:255'],
                'data_model_id' => ['required', 'integer', 'exists:data_models,id'],
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
                'title'         => $data['title'],
                'content'       => null,
                'source'        => 'datamodel',
                'file_name'     => null,
                'data_model_id' => $data['data_model_id'],
                'query_sql'     => $querySql,
                'is_active'     => $request->boolean('is_active', true),
            ]);
        } else {
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
        }

        return redirect()->route('backoffice.knowledge-base.index')
            ->with('success', 'Knowledge base entry created.');
    }

    public function edit(KnowledgeBase $knowledgeBase): View
    {
        $dataModels = DataModel::query()->orderBy('model_name')->get(['id', 'model_name', 'slug']);

        return view('backoffice.knowledge-base.edit', [
            'entry'         => $knowledgeBase,
            'dataModels'    => $dataModels,
            'systemConfigs' => SystemConfig::orderBy('key')->get(['key', 'value']),
            'boActive'      => 'knowledge-base',
        ]);
    }

    public function update(Request $request, KnowledgeBase $knowledgeBase): RedirectResponse
    {
        $sourceType = $request->input('source_type', $knowledgeBase->source);

        if ($sourceType === 'datamodel') {
            $data = $request->validate([
                'title'         => ['required', 'string', 'max:255'],
                'data_model_id' => ['required', 'integer', 'exists:data_models,id'],
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
                'title'   => ['required', 'string', 'max:255'],
                'content' => ['nullable', 'string'],
                'file'    => ['nullable', 'file', 'mimes:txt', 'max:2048'],
            ]);

            $content  = $data['content'] ?? $knowledgeBase->content;
            $source   = in_array($knowledgeBase->source, ['manual', 'file']) ? $knowledgeBase->source : 'manual';
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
