<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\DatabaseConnection;
use App\Models\DataModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DataModelController extends Controller
{
    public function index(): View
    {
        $dataModels = DataModel::query()->orderBy('model_name')->get();

        return view('backoffice.data-models.index', [
            'dataModels' => $dataModels,
            'boActive' => 'data-models',
        ]);
    }

    public function create(): View
    {
        return view('backoffice.data-models.create', [
            'boActive' => 'data-models',
            'connections' => DatabaseConnection::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'model_name' => ['required', 'string', 'max:120', 'regex:/^[a-zA-Z0-9_\\-]+$/', 'unique:data_models,model_name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'table_name' => ['required', 'string', 'max:120', 'regex:/^[a-zA-Z0-9_]+$/'],
            'fields' => ['nullable', 'array'],
            'fields.*.name' => ['required_with:fields', 'string', 'max:120'],
            'fields.*.type' => ['required_with:fields', 'string', 'max:120'],
            'fields.*.required' => ['nullable'],
            'fields.*.value' => ['nullable', 'string', 'max:500'],
            'connection_name' => ['required', 'string', 'exists:database_connections,name'],
        ]);

        DataModel::create([
            'model_name' => trim($data['model_name']),
            'slug' => Str::slug($data['model_name']),
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'table_name' => trim($data['table_name']),
            'connection_name' => $data['connection_name'],
            'fields' => $this->buildFieldMap((array) ($data['fields'] ?? [])),
        ]);

        return redirect()->route('backoffice.data-models.index')->with('success', 'Data model berhasil ditambahkan.');
    }

    public function edit(DataModel $dataModel): View
    {
        return view('backoffice.data-models.edit', [
            'dataModel' => $dataModel,
            'boActive' => 'data-models',
            'connections' => DatabaseConnection::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, DataModel $dataModel): RedirectResponse
    {
        $data = $request->validate([
            'model_name' => ['required', 'string', 'max:120', 'regex:/^[a-zA-Z0-9_\\-]+$/', 'unique:data_models,model_name,' . $dataModel->id],
            'description' => ['nullable', 'string', 'max:1000'],
            'table_name' => ['required', 'string', 'max:120', 'regex:/^[a-zA-Z0-9_]+$/'],
            'fields' => ['nullable', 'array'],
            'fields.*.name' => ['required_with:fields', 'string', 'max:120'],
            'fields.*.type' => ['required_with:fields', 'string', 'max:120'],
            'fields.*.required' => ['nullable'],
            'fields.*.value' => ['nullable', 'string', 'max:500'],
            'connection_name' => ['required', 'string', 'exists:database_connections,name'],
        ]);

        $dataModel->update([
            'model_name' => trim($data['model_name']),
            'slug' => Str::slug($data['model_name']),
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'table_name' => trim($data['table_name']),
            'connection_name' => $data['connection_name'],
            'fields' => $this->buildFieldMap((array) ($data['fields'] ?? [])),
        ]);

        return redirect()->route('backoffice.data-models.index')->with('success', 'Data model berhasil diperbarui.');
    }

    public function destroy(DataModel $dataModel): RedirectResponse
    {
        $name = $dataModel->model_name;
        $dataModel->delete();

        return redirect()->route('backoffice.data-models.index')->with('success', $name . ' berhasil dihapus.');
    }

    private function buildFieldMap(array $rows): array
    {
        $fields = [];

        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $type = trim((string) ($row['type'] ?? ''));
            $required = !empty($row['required']);
            $value = trim((string) ($row['value'] ?? ''));

            if ($name === '' || $type === '') {
                continue;
            }

            $entry = [
                'type' => $type,
                'required' => $required,
            ];

            if ($required && $value !== '') {
                $entry['value'] = $value;
            }

            $fields[$name] = $entry;
        }

        return $fields;
    }
}
