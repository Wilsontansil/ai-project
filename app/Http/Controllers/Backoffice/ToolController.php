<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\DataModel;
use App\Models\ProjectSetting;
use App\Models\Tool;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class ToolController extends Controller
{
    public function index(): View
    {
        $tools = Tool::query()
            ->where('tool_name', '!=', '_bot_config')
            ->orderBy('id')
            ->get();

        return view('backoffice.tools.index', [
            'tools' => $tools,
            'boActive' => 'tools',
        ]);
    }

    public function create(): View
    {
        return view('backoffice.tools.create', [
            'boActive' => 'tools',
            'dataModels' => DataModel::query()->orderBy('model_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tool_name' => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z0-9_-]+$/', 'unique:tools,tool_name'],
            'display_name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'params' => ['nullable', 'array'],
            'params.*.name' => ['required_with:params', 'string', 'max:80'],
            'params.*.description' => ['nullable', 'string', 'max:255'],
            'keywords' => ['nullable', 'string'],
            'missing_message' => ['nullable', 'string', 'max:1000'],
            'information_text' => ['nullable', 'string', 'max:2000'],
            'data_model_id' => ['nullable', 'integer', 'exists:data_models,id'],
            'endpoint_get_route' => ['nullable', 'string', 'max:255'],
            'endpoint_get_expected_status' => ['nullable', 'integer'],
            'endpoint_get_expected_message' => ['nullable', 'string', 'max:255'],
            'endpoint_get_expected_data' => ['nullable', 'array'],
            'endpoint_get_expected_data.*.key' => ['required_with:endpoint_get_expected_data', 'string', 'max:120'],
            'endpoint_get_expected_data.*.value' => ['nullable', 'string', 'max:255'],
            'endpoint_get_body' => ['nullable', 'array'],
            'endpoint_get_body.*.key' => ['required_with:endpoint_get_body', 'string', 'max:80'],
            'endpoint_get_body.*.value' => ['nullable', 'string', 'max:255'],
            'endpoint_update_route' => ['nullable', 'string', 'max:255'],
            'endpoint_update_expected_status' => ['nullable', 'integer'],
            'endpoint_update_expected_message' => ['nullable', 'string', 'max:255'],
            'endpoint_update_expected_data' => ['nullable', 'array'],
            'endpoint_update_expected_data.*.key' => ['required_with:endpoint_update_expected_data', 'string', 'max:120'],
            'endpoint_update_expected_data.*.value' => ['nullable', 'string', 'max:255'],
            'endpoint_update_body' => ['nullable', 'array'],
            'endpoint_update_body.*.key' => ['required_with:endpoint_update_body', 'string', 'max:80'],
            'endpoint_update_body.*.value' => ['nullable', 'string', 'max:255'],
        ]);

        $this->validateDataModelRules($data);

        $parameters = $this->buildParametersFromInput($request->input('params', []));
        $endpoints = $this->buildEndpointsFromInput($request);

        $keywords = null;
        if (!empty($data['keywords'])) {
            $keywords = array_map('trim', explode(',', $data['keywords']));
            $keywords = array_values(array_filter($keywords, fn ($k) => $k !== ''));
        }

        Tool::create([
            'tool_name' => trim($data['tool_name']),
            'display_name' => trim($data['display_name']),
            'description' => trim($data['description'] ?? ''),
            'slug' => Str::slug($data['tool_name']),
            'is_enabled' => $request->boolean('is_enabled'),
            'data_model_id' => $data['data_model_id'] ?? null,
            'parameters' => $parameters,
            'endpoints' => $endpoints,
            'keywords' => $keywords,
            'missing_message' => trim($data['missing_message'] ?? '') ?: null,
            'information_text' => trim($data['information_text'] ?? '') ?: null,
            'meta' => [
                'icon' => trim($request->input('icon', 'M13 10V3L4 14h7v7l9-11h-7z')),
            ],
        ]);

        return redirect()->route('backoffice.tools.index')->with('success', 'Tool berhasil ditambahkan.');
    }

    public function edit(Tool $tool): View
    {
        return view('backoffice.tools.edit', [
            'tool' => $tool,
            'boActive' => 'tools',
            'dataModels' => DataModel::query()->orderBy('model_name')->get(),
        ]);
    }

    public function update(Request $request, Tool $tool): RedirectResponse
    {
        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'params' => ['nullable', 'array'],
            'params.*.name' => ['required_with:params', 'string', 'max:80'],
            'params.*.description' => ['nullable', 'string', 'max:255'],
            'keywords' => ['nullable', 'string'],
            'missing_message' => ['nullable', 'string', 'max:1000'],
            'information_text' => ['nullable', 'string', 'max:2000'],
            'data_model_id' => ['nullable', 'integer', 'exists:data_models,id'],
            'endpoint_get_route' => ['nullable', 'string', 'max:255'],
            'endpoint_get_expected_status' => ['nullable', 'integer'],
            'endpoint_get_expected_message' => ['nullable', 'string', 'max:255'],
            'endpoint_get_expected_data' => ['nullable', 'array'],
            'endpoint_get_expected_data.*.key' => ['required_with:endpoint_get_expected_data', 'string', 'max:120'],
            'endpoint_get_expected_data.*.value' => ['nullable', 'string', 'max:255'],
            'endpoint_get_body' => ['nullable', 'array'],
            'endpoint_get_body.*.key' => ['required_with:endpoint_get_body', 'string', 'max:80'],
            'endpoint_get_body.*.value' => ['nullable', 'string', 'max:255'],
            'endpoint_update_route' => ['nullable', 'string', 'max:255'],
            'endpoint_update_expected_status' => ['nullable', 'integer'],
            'endpoint_update_expected_message' => ['nullable', 'string', 'max:255'],
            'endpoint_update_expected_data' => ['nullable', 'array'],
            'endpoint_update_expected_data.*.key' => ['required_with:endpoint_update_expected_data', 'string', 'max:120'],
            'endpoint_update_expected_data.*.value' => ['nullable', 'string', 'max:255'],
            'endpoint_update_body' => ['nullable', 'array'],
            'endpoint_update_body.*.key' => ['required_with:endpoint_update_body', 'string', 'max:80'],
            'endpoint_update_body.*.value' => ['nullable', 'string', 'max:255'],
        ]);

        $this->validateDataModelRules($data);

        $parameters = $this->buildParametersFromInput($request->input('params', []));

        $keywords = $tool->keywords;
        if ($request->has('keywords')) {
            $raw = trim($data['keywords'] ?? '');
            if ($raw !== '') {
                $keywords = array_map('trim', explode(',', $raw));
                $keywords = array_values(array_filter($keywords, fn ($k) => $k !== ''));
            } else {
                $keywords = null;
            }
        }

        $tool->update([
            'display_name' => trim($data['display_name']),
            'description' => trim($data['description'] ?? ''),
            'is_enabled' => $request->boolean('is_enabled'),
            'data_model_id' => $data['data_model_id'] ?? null,
            'parameters' => $parameters,
            'endpoints' => $this->buildEndpointsFromInput($request),
            'keywords' => $keywords,
            'missing_message' => trim($data['missing_message'] ?? '') ?: null,
            'information_text' => trim($data['information_text'] ?? '') ?: null,
            'meta' => array_merge($tool->meta ?? [], [
                'icon' => trim($request->input('icon', $tool->meta['icon'] ?? 'M13 10V3L4 14h7v7l9-11h-7z')),
            ]),
        ]);

        return redirect()->route('backoffice.tools.index')->with('success', $tool->display_name . ' berhasil diperbarui.');
    }

    public function destroy(Tool $tool): RedirectResponse
    {
        $name = $tool->display_name;
        $tool->delete();

        return redirect()->route('backoffice.tools.index')->with('success', $name . ' berhasil dihapus.');
    }

    /**
     * Build OpenAI-compatible parameters JSON from simple form input.
     */
    private function buildParametersFromInput(?array $params): ?array
    {
        if (empty($params)) {
            return null;
        }

        $properties = [];
        $required = [];

        foreach ($params as $param) {
            $name = trim((string) ($param['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $properties[$name] = [
                'type' => 'string',
                'description' => trim((string) ($param['description'] ?? '')),
            ];

            if (!empty($param['required'])) {
                $required[] = $name;
            }
        }

        if ($properties === []) {
            return null;
        }

        return [
            'type' => 'object',
            'properties' => $properties,
            'required' => $required,
        ];
    }

    /**
     * Build endpoints config from form input.
     */
    private function buildEndpointsFromInput(Request $request): ?array
    {
        $endpoints = [];

        $getRoute = trim((string) $request->input('endpoint_get_route', ''));
        if ($getRoute !== '') {
            $getBody = $this->buildBodyKeyValue((array) $request->input('endpoint_get_body', []));
            if ($getBody === []) {
                $getBody = $this->buildBodyFromParametersInput((array) $request->input('params', []));
            }
            $endpoints['get'] = [
                'route' => $getRoute,
                'body' => $getBody,
                'expected_response' => $this->buildExpectedResponseFromInput('endpoint_get', $request),
            ];
        }

        $updateRoute = trim((string) $request->input('endpoint_update_route', ''));
        if ($updateRoute !== '') {
            $updateBody = $this->buildBodyKeyValue((array) $request->input('endpoint_update_body', []));
            if ($updateBody === []) {
                $updateBody = $this->buildBodyFromParametersInput((array) $request->input('params', []));
            }
            $endpoints['update'] = [
                'route' => $updateRoute,
                'body' => $updateBody,
                'expected_response' => $this->buildExpectedResponseFromInput('endpoint_update', $request),
            ];
        }

        return $endpoints !== [] ? $endpoints : null;
    }

    /**
     * Convert body rows [{key, value}, ...] into { key: value } map.
     */
    private function buildBodyKeyValue(array $rows): array
    {
        $body = [];
        foreach ($rows as $row) {
            $key = trim((string) ($row['key'] ?? ''));
            if ($key === '') {
                continue;
            }
            $body[$key] = trim((string) ($row['value'] ?? ''));
        }
        return $body;
    }

    /**
     * Build default endpoint body keys from tool parameters (value empty string).
     */
    private function buildBodyFromParametersInput(array $params): array
    {
        $body = [];
        foreach ($params as $param) {
            $name = trim((string) ($param['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $body[$name] = '';
        }

        return $body;
    }

    /**
     * Expected response blueprint format:
     * {
     *   "status": 200,
     *   "message": "Success",
     *   "data": [ ...key-value map... ]
     * }
     */
    private function buildExpectedResponseFromInput(string $prefix, Request $request): array
    {
        $status = (int) $request->input($prefix . '_expected_status', 200);
        $message = trim((string) $request->input($prefix . '_expected_message', 'Success'));
        $rows = (array) $request->input($prefix . '_expected_data', []);

        $data = [];
        foreach ($rows as $row) {
            $key = trim((string) ($row['key'] ?? ''));
            $value = trim((string) ($row['value'] ?? ''));

            if ($key === '' || $value === '') {
                continue;
            }

            $data[$key] = $value;
        }

        return [
            'status' => $status,
            'message' => $message === '' ? 'Success' : $message,
            'data' => $data,
        ];
    }

    /**
     * Enforce optional DataModel linkage rules:
     * - information-only tool may keep data_model_id null.
     * - non-information tool must select data_model_id.
     * - parameters must only use selected data model fields.
     */
    private function validateDataModelRules(array $data): void
    {
        $informationText = trim((string) ($data['information_text'] ?? ''));
        $dataModelId = $data['data_model_id'] ?? null;
        $params = (array) ($data['params'] ?? []);

        if ($informationText === '' && empty($dataModelId)) {
            throw ValidationException::withMessages([
                'data_model_id' => 'Pilih Data Model untuk tool yang bukan information-only.',
            ]);
        }

        if (empty($dataModelId)) {
            if (!empty($params)) {
                throw ValidationException::withMessages([
                    'params' => 'Parameter hanya boleh dipakai jika Data Model dipilih.',
                ]);
            }
            return;
        }

        $dataModel = DataModel::query()->find($dataModelId);
        $allowedFields = array_keys((array) ($dataModel?->fields ?? []));

        foreach ($params as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            if (!in_array($name, $allowedFields, true)) {
                throw ValidationException::withMessages([
                    'params' => "Parameter '{$name}' tidak ada di fields Data Model terpilih.",
                ]);
            }
        }
    }

    /**
     * Test an endpoint by making an HTTP request to webhook_base_url + route.
     */
    public function testEndpoint(Request $request): JsonResponse
    {
        $data = $request->validate([
            'route' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'array'],
        ]);

        $baseUrl = rtrim(ProjectSetting::getValue('webhook_base_url', ''), '/');

        if (empty($baseUrl)) {
            return response()->json(['success' => false, 'error' => 'Webhook base URL belum dikonfigurasi di Settings.'], 422);
        }

        $route = '/' . ltrim($data['route'], '/');
        $url = $baseUrl . $route;

        $body = $data['body'] ?? [];

        Log::info('Webhook test request', [
            'channel' => 'tool.test_endpoint',
            'method' => 'POST',
            'base_url' => $baseUrl,
            'route' => $route,
            'url' => $url,
            'body' => $body,
            'timestamp' => now()->toIso8601String(),
        ]);

        try {
            $response = Http::timeout(15)->post($url, $body);

            Log::info('Webhook test response', [
                'channel' => 'tool.test_endpoint',
                'method' => 'POST',
                'base_url' => $baseUrl,
                'route' => $route,
                'url' => $url,
                'status' => $response->status(),
                'successful' => $response->successful(),
                'response_preview' => mb_substr($response->body(), 0, 1000),
                'timestamp' => now()->toIso8601String(),
            ]);

            return response()->json([
                'success' => $response->successful(),
                'status' => $response->status(),
                'url' => $url,
                'body_sent' => $body,
                'response' => $response->json() ?? $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Webhook test exception', [
                'channel' => 'tool.test_endpoint',
                'method' => 'POST',
                'base_url' => $baseUrl,
                'route' => $route,
                'url' => $url,
                'body' => $body,
                'error' => $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ]);

            return response()->json([
                'success' => false,
                'url' => $url,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
