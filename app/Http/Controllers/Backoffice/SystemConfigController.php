<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\DataModel;
use App\Models\SystemConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SystemConfigController extends Controller
{
    public function index(Request $request): View
    {
        $search = (string) $request->query('search', '');

        $query = SystemConfig::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('key', 'like', '%' . $search . '%')
                  ->orWhere('value', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $configs = $query->orderBy('key')->paginate(25)->withQueryString();

        return view('backoffice.system-config.index', [
            'configs'  => $configs,
            'search'   => $search,
            'boActive' => 'system-config',
        ]);
    }

    public function create(): View
    {
        return view('backoffice.system-config.create', [
            'dataModels' => DataModel::query()->orderBy('model_name')->get(['id', 'model_name', 'table_name', 'fields']),
            'boActive'   => 'system-config',
        ]);
    }

    public function edit(SystemConfig $systemConfig): View
    {
        return view('backoffice.system-config.edit', [
            'config'     => $systemConfig,
            'dataModels' => DataModel::query()->orderBy('model_name')->get(['id', 'model_name', 'table_name', 'fields']),
            'boActive'   => 'system-config',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'key'      => ['required', 'string', 'max:191', 'unique:system_configs,key'],
            'value'    => ['nullable', 'string'],
            'description' => ['nullable', 'string', 'max:1000'],
            'source_type' => ['required', Rule::in(['manual', 'datamodel_lookup'])],
            'data_model_id' => ['nullable', 'integer', 'exists:data_models,id'],
            'lookup_field' => ['nullable', 'string', 'max:191'],
            'lookup_value' => ['nullable', 'string'],
            'result_field' => ['nullable', 'string', 'max:191'],
            'from_agent' => ['nullable', 'integer'],
        ]);

        $this->validateLookupFields($data);

        $record = SystemConfig::create([
            'key' => $data['key'],
            'value' => $data['source_type'] === 'manual' ? ($data['value'] ?? null) : null,
            'description' => $data['description'] ?? null,
            'source_type' => $data['source_type'],
            'data_model_id' => $data['source_type'] === 'datamodel_lookup' ? ($data['data_model_id'] ?? null) : null,
            'lookup_field' => $data['source_type'] === 'datamodel_lookup' ? ($data['lookup_field'] ?? null) : null,
            'lookup_value' => $data['source_type'] === 'datamodel_lookup' ? ($data['lookup_value'] ?? null) : null,
            'result_field' => $data['source_type'] === 'datamodel_lookup' ? ($data['result_field'] ?? null) : null,
        ]);

        if ($record->source_type === 'datamodel_lookup') {
            $resolved = $record->resolveEffectiveValue();
            if ($resolved !== null) {
                $record->updateQuietly(['value' => $resolved]);
            }
        }

        return $this->redirectBack($request);
    }

    public function update(Request $request, SystemConfig $systemConfig): RedirectResponse
    {
        $data = $request->validate([
            'key'   => ['required', 'string', 'max:191', 'unique:system_configs,key,' . $systemConfig->id],
            'value' => ['nullable', 'string'],
            'description' => ['nullable', 'string', 'max:1000'],
            'source_type' => ['required', Rule::in(['manual', 'datamodel_lookup'])],
            'data_model_id' => ['nullable', 'integer', 'exists:data_models,id'],
            'lookup_field' => ['nullable', 'string', 'max:191'],
            'lookup_value' => ['nullable', 'string'],
            'result_field' => ['nullable', 'string', 'max:191'],
        ]);

        $this->validateLookupFields($data);

        $systemConfig->update([
            'key' => $data['key'],
            'value' => $data['source_type'] === 'manual' ? ($data['value'] ?? null) : null,
            'description' => $data['description'] ?? null,
            'source_type' => $data['source_type'],
            'data_model_id' => $data['source_type'] === 'datamodel_lookup' ? ($data['data_model_id'] ?? null) : null,
            'lookup_field' => $data['source_type'] === 'datamodel_lookup' ? ($data['lookup_field'] ?? null) : null,
            'lookup_value' => $data['source_type'] === 'datamodel_lookup' ? ($data['lookup_value'] ?? null) : null,
            'result_field' => $data['source_type'] === 'datamodel_lookup' ? ($data['result_field'] ?? null) : null,
        ]);

        if ($systemConfig->source_type === 'datamodel_lookup') {
            $systemConfig->refresh();
            $resolved = $systemConfig->resolveEffectiveValue();
            if ($resolved !== null) {
                $systemConfig->updateQuietly(['value' => $resolved]);
            }
        }

        return $this->redirectBack($request);
    }

    public function syncAll(Request $request): RedirectResponse
    {
        $configs = SystemConfig::where('source_type', 'datamodel_lookup')->get();
        foreach ($configs as $config) {
            $resolved = $config->resolveEffectiveValue();
            if ($resolved !== null) {
                $config->updateQuietly(['value' => $resolved]);
            }
        }
        SystemConfig::bumpCacheVersion();

        return $this->redirectBack($request)->with('success', 'All datamodel configs synced successfully.');
    }

    public function destroy(Request $request, SystemConfig $systemConfig): RedirectResponse
    {
        $systemConfig->delete();

        return $this->redirectBack($request);
    }

    private function redirectBack(Request $request): RedirectResponse
    {
        $agentId = (int) $request->input('from_agent', $request->query('from_agent', 0));

        if ($agentId > 0) {
            return redirect()->route('backoffice.chat-agents.edit', [
                'chatAgent' => $agentId,
                'tab'       => 'system-config',
            ])->with('success', 'System config saved.');
        }

        return redirect()->route('backoffice.system-config.index')->with('success', 'System config saved.');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateLookupFields(array $data): void
    {
        if (($data['source_type'] ?? 'manual') !== 'datamodel_lookup') {
            return;
        }

        $dataModelId = (int) ($data['data_model_id'] ?? 0);
        $lookupField = trim((string) ($data['lookup_field'] ?? ''));
        $lookupValue = trim((string) ($data['lookup_value'] ?? ''));
        $resultField = trim((string) ($data['result_field'] ?? ''));

        if ($dataModelId <= 0 || $lookupField === '' || $lookupValue === '' || $resultField === '') {
            throw ValidationException::withMessages([
                'source_type' => 'DataModel, lookup field, lookup value, and result field are required for datamodel source.',
            ]);
        }

        $dataModel = DataModel::query()->find($dataModelId);
        $allowedFields = array_keys((array) ($dataModel?->fields ?? []));

        if (!in_array($lookupField, $allowedFields, true) || !in_array($resultField, $allowedFields, true)) {
            throw ValidationException::withMessages([
                'source_type' => 'Selected lookup/result fields are not available in the selected DataModel.',
            ]);
        }
    }
}
