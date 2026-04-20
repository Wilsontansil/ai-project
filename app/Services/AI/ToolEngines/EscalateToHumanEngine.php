<?php

namespace App\Services\AI\ToolEngines;

use App\Models\Customer;
use App\Models\Tool;
use Illuminate\Support\Facades\Log;

class EscalateToHumanEngine
{
    /**
     * @param array<string, mixed> $arguments  Expects 'chat_id' and 'channel' from context.
     * @return array{mode: string, reply?: string, tool_context?: array<string, mixed>}
     */
    public function execute(Tool $tool, array $arguments): array
    {
        $chatId = (string) ($arguments['chat_id'] ?? '');
        $channel = (string) ($arguments['channel'] ?? '');

        if ($chatId === '' || $channel === '') {
            return [
                'mode' => 'direct',
                'reply' => 'Gagal eskalasi — data customer tidak tersedia.',
            ];
        }

        $customer = Customer::query()
            ->where('platform', $channel)
            ->where('platform_user_id', $chatId)
            ->first();

        if ($customer === null) {
            return [
                'mode' => 'direct',
                'reply' => 'Gagal eskalasi — customer tidak ditemukan.',
            ];
        }

        $customer->update(['mode' => 'waiting']);

        Log::info('Customer escalated to human support', [
            'customer_id' => $customer->id,
            'platform' => $channel,
            'chat_id' => $chatId,
        ]);

        return [
            'mode' => 'model',
            'tool_context' => [
                'execution_type' => 'escalate_to_human',
                'tool_name' => 'escalate_to_human',
                'tool_display_name' => 'Eskalasi ke Human',
                'success' => true,
                'response_message' => 'Customer berhasil dieskalasi ke human support. Mode customer sekarang: waiting. Sampaikan ke customer bahwa CS akan segera menghubungi mereka, dan berikan kontak support sesuai platform.',
            ],
        ];
    }
}
