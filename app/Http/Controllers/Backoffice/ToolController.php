<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\DataModel;
use App\Models\ProjectSetting;
use App\Models\Tool;
use App\Support\LogSanitizer;
use App\Support\ResilientHttp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class ToolController extends Controller
{
    private const CATEGORIES = ['account', 'sports', 'games', 'promo', 'bonus', 'payment', 'general', 'system', 'lottery'];

    public function index(Request $request): View
    {
        $category = $request->query('category');

        $query = Tool::query()
            ->where('tool_name', '!=', '_bot_config')
            ->orderBy('id');

        if ($category && in_array($category, self::CATEGORIES, true)) {
            $query->where('category', $category);
        }

        return view('backoffice.tools.index', [
            'tools'            => $query->get(),
            'boActive'         => 'tools',
            'categories'       => self::CATEGORIES,
            'selectedCategory' => $category,
        ]);
    }

    public function create(): View
    {
        return view('backoffice.tools.create', [
            'boActive'   => 'tools',
            'dataModels' => DataModel::query()->orderBy('model_name')->get(),
            'categories' => self::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->toolValidationRules(true));

        $this->validateDataModelRules($data);

        Tool::create($this->buildToolPayload($request, $data));

        if ($fromAgent = $request->input('from_agent')) {
            return redirect()->route('backoffice.chat-agents.edit', ['chatAgent' => $fromAgent, 'tab' => 'tools'])
                ->with('success', 'Tool berhasil ditambahkan.');
        }

        return redirect()->route('backoffice.tools.index')->with('success', 'Tool berhasil ditambahkan.');
    }

    public function edit(Tool $tool): View
    {
        return view('backoffice.tools.edit', [
            'tool'       => $tool,
            'boActive'   => 'tools',
            'dataModels' => DataModel::query()->orderBy('model_name')->get(),
            'categories' => self::CATEGORIES,
        ]);
    }

    public function update(Request $request, Tool $tool): RedirectResponse
    {
        $data = $request->validate($this->toolValidationRules());

        $this->validateDataModelRules($data);

        $tool->update($this->buildToolPayload($request, $data, $tool));

        if ($fromAgent = $request->input('from_agent')) {
            return redirect()->route('backoffice.chat-agents.edit', ['chatAgent' => $fromAgent, 'tab' => 'tools'])
                ->with('success', $tool->display_name . ' berhasil diperbarui.');
        }

        return redirect()->route('backoffice.tools.index')->with('success', $tool->display_name . ' berhasil diperbarui.');
    }

    public function destroy(Tool $tool): RedirectResponse
    {
        $name = $tool->display_name;
        $fromAgent = $request->input('from_agent');
        $tool->delete();

        if ($fromAgent) {
            return redirect()->route('backoffice.chat-agents.edit', ['chatAgent' => $fromAgent, 'tab' => 'tools'])
                ->with('success', $name . ' berhasil dihapus.');
        }

        return redirect()->route('backoffice.tools.index')->with('success', $name . ' berhasil dihapus.');
    }

    private function toolValidationRules(bool $isCreate = false): array
    {
        $rules = [
            'type' => ['required', 'string', 'in:info,get,update,get_multiple,web_scraper'],
            'display_name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:100'],
            'params' => ['nullable', 'array'],
            'params.*.name' => ['required_with:params', 'string', 'max:80'],
            'params.*.description' => ['nullable', 'string', 'max:255'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'tool_rules' => ['nullable', 'string', 'max:500'],
            'information_texts' => ['nullable', 'array'],
            'information_texts.*' => ['nullable', 'string', 'max:500'],
            'data_model_id' => ['nullable', 'integer', 'exists:data_models,id'],
            'data_model_ids' => ['nullable', 'array'],
            'data_model_ids.*' => ['integer', 'exists:data_models,id'],
            'endpoint_route' => ['nullable', 'string', 'max:255'],
            'endpoint_expected_status' => ['nullable', 'integer'],
            'endpoint_expected_message' => ['nullable', 'string', 'max:255'],
            'endpoint_expected_data' => ['nullable', 'array'],
            'endpoint_expected_data.*.key' => ['required_with:endpoint_expected_data', 'string', 'max:120'],
            'endpoint_expected_data.*.value' => ['nullable', 'string', 'max:255'],
            'endpoint_body' => ['nullable', 'array'],
            'endpoint_body.*.key' => ['required_with:endpoint_body', 'string', 'max:80'],
            'endpoint_body.*.value' => ['nullable', 'string', 'max:255'],
            'error_responses' => ['nullable', 'array'],
            'error_responses.*.status' => ['required_with:error_responses', 'integer'],
            'error_responses.*.message' => ['required_with:error_responses', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'in:' . implode(',', self::CATEGORIES)],
        ];

        if ($isCreate) {
            $rules['tool_name'] = ['required', 'string', 'max:80', 'regex:/^[a-zA-Z0-9_-]+$/', 'unique:tools,tool_name'];
        }

        return $rules;
    }

    private function buildToolPayload(Request $request, array $data, ?Tool $tool = null): array
    {
        $type = $data['type'];

        $payload = [
            'type' => $type,
            'display_name' => trim($data['display_name']),
            'description' => trim($data['description'] ?? ''),
            'data_model_id' => $type === 'get' ? ($data['data_model_id'] ?? null) : null,
            'parameters' => $this->buildParametersFromInput($request->input('params', [])),
            'endpoints' => $this->buildEndpointsFromInput($request),
            'keywords' => $this->normalizeKeywords($request, $data, $tool),
            'tool_rules' => trim($data['tool_rules'] ?? '') ?: null,
            'information_text' => $this->buildInformationTexts($request),
            'meta'             => $this->buildToolMeta($request, $tool, $type),
            'category'         => $data['category'] ?? null,
        ];

        if ($tool === null) {
            $toolName = trim($data['tool_name']);
            $payload['tool_name'] = $toolName;
            $payload['slug'] = Str::slug($toolName);
        }

        return $payload;
    }

    private function normalizeKeywords(Request $request, array $data, ?Tool $tool = null): ?array
    {
        if ($tool !== null && !$request->has('keywords')) {
            return $tool->keywords;
        }

        $raw = trim((string) ($data['keywords'] ?? ''));
        if ($raw === '') {
            return null;
        }

        $keywords = array_map('trim', explode(',', $raw));

        return array_values(array_filter($keywords, fn ($keyword) => $keyword !== ''));
    }

    private function buildToolMeta(Request $request, ?Tool $tool = null, ?string $type = null): array
    {
        $meta = $tool ? ($tool->meta ?? []) : [];

        if ($type === 'get_multiple') {
            $ids = array_filter(array_map('intval', (array) $request->input('data_model_ids', [])));
            $meta['data_model_ids'] = array_values($ids);

            // Preserve meta.query config (filters, date_range, aggregate, order_by, etc.)
            // Query config is managed via seeder / direct DB; the form does not overwrite it.
        } else {
            unset($meta['data_model_ids'], $meta['query']);
        }

        return $meta;
    }

    private function buildInformationTexts(Request $request): ?array
    {
        $texts = array_filter(
            array_map('trim', (array) $request->input('information_texts', [])),
            fn ($t) => $t !== ''
        );

        return count($texts) > 0 ? array_values($texts) : null;
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
        $route = trim((string) $request->input('endpoint_route', ''));
        if ($route === '') {
            return null;
        }

        $body = $this->buildBodyKeyValue((array) $request->input('endpoint_body', []));
        if ($body === []) {
            $body = $this->buildBodyFromParametersInput((array) $request->input('params', []));
        }

        return [
            'endpoint' => [
                'route' => $route,
                'body' => $body,
                'expected_response' => $this->buildExpectedResponseFromInput('endpoint', $request),
                'error_responses' => $this->buildErrorResponsesFromInput($request),
            ],
        ];
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
     * Build error responses array from form input.
     */
    private function buildErrorResponsesFromInput(Request $request): array
    {
        $rows = (array) $request->input('error_responses', []);
        $errors = [];

        foreach ($rows as $row) {
            $status = (int) ($row['status'] ?? 0);
            $message = trim((string) ($row['message'] ?? ''));

            if ($status === 0 || $message === '') {
                continue;
            }

            $errors[] = [
                'status' => $status,
                'message' => $message,
                'data' => new \stdClass(),
            ];
        }

        return $errors;
    }

    /**
     * Enforce optional DataModel linkage rules:
     * - information-only tool may keep data_model_id null.
     * - non-information tool must select data_model_id.
     * - parameters must only use selected data model fields.
     */
    private function validateDataModelRules(array $data): void
    {
        $type = $data['type'] ?? 'info';

        if ($type === 'info') {
            $infoTexts = array_filter(array_map('trim', (array) ($data['information_texts'] ?? [])), fn ($t) => $t !== '');
            if (count($infoTexts) === 0) {
                throw ValidationException::withMessages([
                    'information_texts' => 'Tool bertipe Info harus memiliki minimal satu Information Text.',
                ]);
            }
            return;
        }

        if ($type === 'get') {
            if (empty($data['data_model_id'])) {
                throw ValidationException::withMessages([
                    'data_model_id' => 'Tool bertipe Get harus memilih Data Model.',
                ]);
            }

            $dataModel = DataModel::query()->find($data['data_model_id']);
            // @phpstan-ignore nullsafe.neverNull
            $allowedFields = array_keys((array) ($dataModel?->fields ?? []));

            foreach ((array) ($data['params'] ?? []) as $row) {
                $name = trim((string) ($row['name'] ?? ''));
                if ($name === '' || in_array($name, $allowedFields, true)) {
                    continue;
                }
                throw ValidationException::withMessages([
                    'params' => "Parameter '{$name}' tidak ada di fields Data Model terpilih.",
                ]);
            }
            return;
        }

        if ($type === 'update') {
            $route = trim((string) ($data['endpoint_route'] ?? ''));
            if ($route === '') {
                throw ValidationException::withMessages([
                    'endpoint_route' => 'Tool bertipe Update harus memiliki endpoint route.',
                ]);
            }
            return;
        }

        if ($type === 'get_multiple') {
            $ids = array_filter(array_map('intval', (array) ($data['data_model_ids'] ?? [])));
            if (count($ids) === 0) {
                throw ValidationException::withMessages([
                    'data_model_ids' => 'Tool bertipe Get Multiple harus memilih minimal satu Data Model.',
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

        $url = $data['route'];

        $body = $data['body'] ?? [];

        Log::info('Webhook test request', [
            'channel' => 'tool.test_endpoint',
            'method' => 'POST',
            'url' => $url,
            'body' => LogSanitizer::redactArguments($body),
            'timestamp' => now()->toIso8601String(),
        ]);

        try {
            $response = ResilientHttp::post(
                service: 'tool-test-endpoint',
                url: $url,
                payload: $body,
                timeoutSeconds: 15
            );

            if ($response === null) {
                return response()->json([
                    'success' => false,
                    'url' => $url,
                    'error' => 'Request blocked or failed after retries. Please try again shortly.',
                ], 503);
            }

            Log::info('Webhook test response', [
                'channel' => 'tool.test_endpoint',
                'method' => 'POST',
                'base_url' => $baseUrl,
                'route' => $route,
                'url' => $url,
                'status' => $response->status(),
                'successful' => $response->successful(),
                'response_summary' => is_array($response->json())
                    ? LogSanitizer::summarize($response->json())
                    : ['size_bytes' => mb_strlen($response->body())],
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
                'body' => LogSanitizer::redactArguments($body),
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
