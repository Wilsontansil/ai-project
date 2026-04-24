<?php

namespace App\Services\AI\ToolEngines;

use App\Models\ProjectSetting;
use App\Models\Tool;
use App\Services\AI\Concerns\BuildsMissingDataMessage;
use App\Support\LogSanitizer;
use App\Support\ResilientHttp;
use Illuminate\Support\Facades\Log;

/**
 * Executes HTTP endpoint ("update") type tools.
 *
 * Endpoint config lives in tool.endpoints['endpoint']:
 * {
 *   "route": "/api/some-action",
 *   "body": { "username": "$arg->username", "type": "player" },
 *   "expected_response": { "status": 200, "message": "Success", "data": {} }
 * }
 *
 * - Body template values starting with "$arg->field" are resolved from call arguments.
 * - Missing required fields return the configured missing-data prompt.
 * - Non-success responses that carry a readable status+message are forwarded to the AI
 *   so it can explain the result conversationally.
 */
class HttpToolEngine
{
    use BuildsMissingDataMessage;

    private const USER_FACING_ERROR = 'Maaf, permintaan belum bisa diproses saat ini. Silakan coba lagi beberapa saat ya.';

    /**
     * @param array<string, mixed> $arguments
     * @return array{mode: string, reply?: string, tool_context?: array<string, mixed>}
     */
    public function execute(Tool $tool, array $arguments): array
    {
        $endpointConfig = $tool->endpoints['endpoint'] ?? null;

        if (!is_array($endpointConfig)) {
            return ['mode' => 'direct', 'reply' => self::USER_FACING_ERROR];
        }

        $route = trim((string) ($endpointConfig['route'] ?? ''));
        if ($route === '') {
            return ['mode' => 'direct', 'reply' => self::USER_FACING_ERROR];
        }

        // Validate required parameters are present before building the request.
        $requiredFields = (array) data_get($tool->parameters, 'required', []);
        foreach ($requiredFields as $requiredField) {
            $value = trim((string) ($arguments[$requiredField] ?? ''));
            if ($value === '') {
                return ['mode' => 'direct', 'reply' => $this->buildMissingDataMessage($tool)];
            }
        }

        $bodyTemplate = (array) ($endpointConfig['body'] ?? []);
        $builtBody = $this->buildRequestBody($arguments, $bodyTemplate);

        if ($builtBody['ok'] !== true) {
            Log::warning('HTTP endpoint body template unresolved', [
                'tool_name' => $tool->tool_name,
                'error' => $builtBody['error'] ?? 'unknown',
                'arguments' => LogSanitizer::redactArguments($arguments),
            ]);

            return ['mode' => 'direct', 'reply' => self::USER_FACING_ERROR];
        }

        $requestBody = (array) ($builtBody['body'] ?? []);
        $expectedResponse = $endpointConfig['expected_response'] ?? [
            'status' => 200,
            'message' => 'Success',
            'data' => [],
        ];

        $webhookBaseUrl = trim((string) ProjectSetting::getValue('webhook_base_url', ''));
        if ($webhookBaseUrl === '') {
            Log::warning('Webhook base URL not configured', ['tool_name' => $tool->tool_name]);

            return ['mode' => 'direct', 'reply' => self::USER_FACING_ERROR];
        }

        $fullUrl = rtrim($webhookBaseUrl, '/') . $route;

        Log::info('Executing HTTP endpoint', [
            'tool_name' => $tool->tool_name,
            'url' => $fullUrl,
            'request_keys' => array_keys($requestBody),
        ]);

        try {
            $response = ResilientHttp::post(
                service: 'tool-endpoint:' . $tool->tool_name,
                url: $fullUrl,
                payload: $requestBody,
                timeoutSeconds: 10
            );

            if ($response === null) {
                return ['mode' => 'direct', 'reply' => self::USER_FACING_ERROR];
            }

            $statusCode = $response->status();
            $responseBody = $response->json() ?? [];

            $validation = $this->validateResponse(
                $tool, $statusCode, $responseBody, $expectedResponse
            );

            if ($validation['valid'] === false) {
                // If the response carries a readable status+message, let the AI explain it.
                if (isset($responseBody['status'], $responseBody['message'])) {
                    Log::info('HTTP endpoint returned non-success response, forwarding to AI', [
                        'tool_name' => $tool->tool_name,
                        'http_status' => $statusCode,
                        'response_message' => $responseBody['message'],
                    ]);

                    return [
                        'mode' => 'model',
                        'tool_context' => [
                            'tool_name' => $tool->tool_name,
                            'tool_display_name' => $tool->display_name,
                            'tool_description' => $tool->description,
                            'execution_type' => 'http_endpoint',
                            'endpoint_route' => $route,
                            'request_parameters' => $requestBody,
                            'http_status_code' => $statusCode,
                            'response_status' => $responseBody['status'],
                            'response_message' => $responseBody['message'],
                            'response_data' => $responseBody['data'] ?? [],
                            'success' => false,
                        ],
                    ];
                }

                return ['mode' => 'direct', 'reply' => self::USER_FACING_ERROR];
            }

            return [
                'mode' => 'model',
                'tool_context' => [
                    'tool_name' => $tool->tool_name,
                    'tool_display_name' => $tool->display_name,
                    'tool_description' => $tool->description,
                    'execution_type' => 'http_endpoint',
                    'endpoint_route' => $route,
                    'request_parameters' => $requestBody,
                    'http_status_code' => $statusCode,
                    'response_status' => $responseBody['status'] ?? $statusCode,
                    'response_message' => $responseBody['message'] ?? '',
                    'response_data' => $responseBody['data'] ?? $responseBody,
                    'success' => true,
                ],
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('HTTP endpoint connection failed', [
                'tool_name' => $tool->tool_name,
                'url' => $fullUrl,
                'request' => LogSanitizer::redactArguments($requestBody),
                'error' => $e->getMessage(),
            ]);

            return ['mode' => 'direct', 'reply' => self::USER_FACING_ERROR];
        } catch (\Throwable $e) {
            Log::error('HTTP endpoint execution failed', [
                'tool_name' => $tool->tool_name,
                'url' => $fullUrl,
                'request' => LogSanitizer::redactArguments($requestBody),
                'error' => $e->getMessage(),
            ]);

            return ['mode' => 'direct', 'reply' => self::USER_FACING_ERROR];
        }
    }

    /**
     * Merge the body template with call arguments.
     * Template values matching "$arg->fieldName" are substituted from $arguments.
     * Literal template values are kept as-is.
     *
     * @param array<string, mixed> $arguments
     * @param array<string, mixed> $bodyTemplate
     * @return array{ok: bool, body?: array<string, mixed>, error?: string}
     */
    private function buildRequestBody(array $arguments, array $bodyTemplate): array
    {
        $body = [];

        foreach ($bodyTemplate as $key => $templateValue) {
            $key = (string) $key;

            if (is_string($templateValue) && trim($templateValue) !== '') {
                $resolved = $this->resolveTemplateValue(trim($templateValue), $arguments);
                if ($resolved['ok'] !== true) {
                    return [
                        'ok' => false,
                        'error' => $resolved['error'] ?? "Unable to resolve endpoint body value for key {$key}",
                    ];
                }

                $body[$key] = $resolved['value'] ?? '';
                continue;
            }

            if (isset($arguments[$key])) {
                $argValue = $arguments[$key];
                $body[$key] = is_string($argValue) ? trim($argValue) : $argValue;
                continue;
            }

            $body[$key] = $templateValue ?? '';
        }

        return ['ok' => true, 'body' => $body];
    }

    /**
     * Resolve a single template placeholder, e.g. "$arg->username" → $arguments['username'].
     *
     * @param array<string, mixed> $arguments
     * @return array{ok: bool, value?: mixed, error?: string}
     */
    private function resolveTemplateValue(string $templateValue, array $arguments): array
    {
        if (preg_match('/^\$arg->([a-zA-Z_][a-zA-Z0-9_]*)$/', $templateValue, $matches) === 1) {
            $field = $matches[1];

            if (!array_key_exists($field, $arguments)) {
                return ['ok' => false, 'error' => "Argument field '{$field}' not found"];
            }

            return ['ok' => true, 'value' => $arguments[$field]];
        }

        return ['ok' => true, 'value' => $templateValue];
    }

    /**
     * Validate HTTP status code and response body structure against expectations.
     *
     * @param array<string, mixed> $responseBody
     * @param array<string, mixed> $expectedResponse
     * @return array{valid: bool, error_message?: string}
     */
    private function validateResponse(
        Tool $tool,
        int $statusCode,
        array $responseBody,
        array $expectedResponse
    ): array {
        $expectedStatus = (int) ($expectedResponse['status'] ?? 200);
        $expectedData = (array) ($expectedResponse['data'] ?? []);

        if ($statusCode !== $expectedStatus) {
            Log::warning('HTTP endpoint status mismatch', [
                'tool_name' => $tool->tool_name,
                'expected_status' => $expectedStatus,
                'actual_status' => $statusCode,
                'response_summary' => LogSanitizer::summarize($responseBody),
            ]);

            return ['valid' => false, 'error_message' => "Endpoint returned status {$statusCode}, expected {$expectedStatus}."];
        }

        if (!isset($responseBody['status'])) {
            Log::warning('HTTP endpoint response structure invalid', [
                'tool_name' => $tool->tool_name,
                'expected_format' => '{ "status": int, "message": string, "data": object }',
                'response_summary' => LogSanitizer::summarize($responseBody),
            ]);

            return ['valid' => false, 'error_message' => 'Endpoint response format tidak sesuai (missing status field).'];
        }

        $bodyStatus = (int) $responseBody['status'];
        if ($bodyStatus !== $expectedStatus) {
            Log::warning('HTTP endpoint body status mismatch', [
                'tool_name' => $tool->tool_name,
                'expected_status' => $expectedStatus,
                'actual_body_status' => $bodyStatus,
            ]);

            return ['valid' => false, 'error_message' => "Response body status {$bodyStatus}, expected {$expectedStatus}."];
        }

        if ($expectedData !== []) {
            $responseData = (array) ($responseBody['data'] ?? []);

            foreach (array_keys($expectedData) as $expectedKey) {
                if (!isset($responseData[$expectedKey])) {
                    Log::warning('HTTP endpoint data field missing', [
                        'tool_name' => $tool->tool_name,
                        'expected_field' => $expectedKey,
                        'response_data_keys' => array_keys($responseData),
                    ]);

                    return ['valid' => false, 'error_message' => "Response data field '{$expectedKey}' tidak ditemukan."];
                }
            }
        }

        return ['valid' => true];
    }
}
    }
}
