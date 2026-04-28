<?php

namespace App\Services\AI;

use App\Models\ChatAgent;
use App\Models\Customer;
use App\Models\ProjectSetting;
use Illuminate\Support\Facades\Log;
use OpenAI;

class EscalationSummaryService
{
    /**
     * Generate and store a one-sentence escalation summary for the customer.
     *
     * Reads the current session messages from ConversationHistory, asks OpenAI to
     * summarise them in 1-2 sentences, and saves the result to
     * customers.escalation_summary.
     */
    public function generate(Customer $customer, string $chatId, string $channel): void
    {
        try {
            $apiKey = (string) ProjectSetting::getValue('openai_api_key', config('services.openai.api_key', ''));
            if ($apiKey === '') {
                return;
            }

            $sessionMessages = app(ConversationHistory::class)->load($chatId, $channel);
            if (empty($sessionMessages)) {
                return;
            }

            $transcript = collect($sessionMessages)->map(function ($msg) {
                $role = $msg['role'] === 'assistant' ? 'Bot' : 'Customer';
                return "{$role}: " . ($msg['content'] ?? '');
            })->implode("\n");

            $client = OpenAI::client($apiKey);
            $agent  = ChatAgent::getDefault();
            $model  = $agent?->model ?? 'gpt-4.1-mini';

            $isReasoningModel = (bool) preg_match('/^o\d/', $model);
            $payload = [
                'model'                 => $model,
                'max_completion_tokens' => 80,
                'messages'              => [
                    [
                        'role'    => 'system',
                        'content' => 'You are a summarizer. In 1-2 short sentences, summarize the customer\'s main issue from the conversation so a human agent can quickly understand why the customer was escalated. Be concise and factual. Reply in the same language as the conversation.',
                    ],
                    [
                        'role'    => 'user',
                        'content' => $transcript,
                    ],
                ],
            ];
            if (! $isReasoningModel) {
                $payload['temperature'] = 0.3;
            }

            $response = $client->chat()->create($payload);
            $summary  = trim($response->choices[0]->message->content ?? '');

            if ($summary !== '') {
                $customer->update(['escalation_summary' => $summary]);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to generate escalation summary', [
                'customer_id' => $customer->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }
}
