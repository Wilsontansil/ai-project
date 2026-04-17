<?php

namespace App\Services\AI;

use App\Models\ChatAgent;
use App\Models\ForbiddenBehaviour;
use App\Models\Tool;
use Illuminate\Support\Facades\Schema;

/**
 * Builds system prompts and agent context injections for OpenAI requests.
 *
 * Responsibilities:
 *   - Compose the full system prompt from ChatAgent config + forbidden behaviours + tool rules.
 *   - Build the per-request customer context block (profile, behaviour, recent history).
 *   - Look up the configured bot name from the _bot_config tool row.
 */
class PromptBuilder
{
    public function buildSystemPrompt(?ChatAgent $chatAgent): string
    {
        $botName = $chatAgent->name ?? $this->getBotName();
        $serverTime = now()->format('Y-m-d H:i:s (l)');
        $serverTimezone = now()->getTimezone()->getName();

        if ($chatAgent && !empty($chatAgent->system_prompt)) {
            $basePrompt = str_replace(
                ['{bot_name}', '{server_time}', '{server_timezone}'],
                [$botName, $serverTime, $serverTimezone],
                $chatAgent->system_prompt
            );
        } else {
            $basePrompt = "You are {$botName}, a friendly customer support assistant for a gaming platform.

        CURRENT SERVER TIME: {$serverTime} ({$serverTimezone})
        Use this as the authoritative current datetime for all time-based calculations (e.g. today, yesterday, last week Monday-Sunday, this month, etc.).

        RULES:
        - Default language: Bahasa Indonesia. Follow user's language if different.
        - Speak naturally, warm, casual-professional — like a real CS agent on chat.
        - Never make up information. Be honest if unsure.
        - If a user asks about account status, suspend status, verification, or any action covered by a configured tool, you MUST use the relevant tool and never guess the answer.
        - For tools linked to a data model, treat database lookup results as the only source of truth.
        - DataModel/game database access is READ-ONLY: never create, update, delete, insert, or alter records/tables when handling DataModel tools.
        - This read-only restriction applies only to DataModel-linked game tables, not to internal application model/workflow handling.
        - Always confirm before performing any sensitive action or updating player data.
        - If input values seem wrong, suggest valid options and ask user to re-check.
        - Stay professional with angry/abusive users — respond politely, add emoji to soften tone.
        - Introduce yourself as {$botName} on first interaction only.
        - Format replies cleanly — no messy line breaks or long unbroken text.

        TOOL DATA:
        - 'bank': BCA, Mandiri, BRI, BNI, Danamon, CIMB Niaga, Permata, Maybank, Panin, BSI, Bank Jago, Bank Mega, Bank Bukopin, OCBC NISP, Mayapada, Sinarmas, Commonwealth, UOB Indonesia, BTN, Bank DKI, BTPN, Artha Graha, Mayora, JTrust Indonesia, Mestika, Victoria, Ina Perdana, Woori Saudara, Artos Indonesia, Harda Internasional, Ganesha, Maspion, QNB Indonesia, Royal Indonesia, Bumi Arta, Nusantara Parahyangan, and their Syariah variants.
        - 'norek': Numeric only.
        ";
        }

        $caseInstructions = $this->getCaseInstructions($chatAgent);
        if ($caseInstructions !== '') {
            $basePrompt .= "\n\n" . $caseInstructions;
        }

        $toolRules = $this->getToolRulesPrompt();
        if ($toolRules !== '') {
            $basePrompt .= "\n\n" . $toolRules;
        }

        return $basePrompt;
    }

    /**
     * Build the optional customer context block injected as a second system message.
     *
     * @param array<string, mixed> $context
     */
    public function buildAgentContextPrompt(array $context): ?string
    {
        if ($context === []) {
            return null;
        }

        $profile = (array) ($context['customer_profile'] ?? []);
        $behavior = (array) ($context['behavior'] ?? []);
        $recentConversation = trim((string) ($context['recent_conversation'] ?? ''));

        $parts = [
            'Customer context (internal only — do not expose to user):',
        ];

        if ($profile !== []) {
            $parts[] = 'Profile: ' . json_encode([
                'platform' => $profile['platform'] ?? null,
                'name' => $profile['name'] ?? null,
                'total_messages' => $profile['total_messages'] ?? null,
                'tags' => $profile['tags'] ?? [],
            ], JSON_UNESCAPED_UNICODE);
        }

        if ($behavior !== []) {
            $parts[] = 'Behavior: ' . json_encode([
                'intent' => $behavior['intent'] ?? null,
                'sentiment' => $behavior['sentiment'] ?? null,
                'frequency_score' => $behavior['frequency_score'] ?? null,
            ], JSON_UNESCAPED_UNICODE);
        }

        if ($recentConversation !== '') {
            $parts[] = "Recent conversation:\n" . mb_substr($recentConversation, 0, 1200);
        }

        return implode("\n\n", $parts);
    }

    private function getToolRulesPrompt(): string
    {
        if (!Schema::hasTable('tools')) {
            return '';
        }

        $tools = Tool::query()
            ->where('is_enabled', true)
            ->where('tool_name', '!=', '_bot_config')
            ->get();

        $lines = [];

        foreach ($tools as $tool) {
            $rules = trim((string) ($tool->tool_rules ?? ''));
            if ($rules === '') {
                continue;
            }

            $lines[] = "TOOL [{$tool->tool_name}] ({$tool->display_name}) RULES:\n{$rules}";
        }

        if ($lines === []) {
            return '';
        }

        return "PER-TOOL INSTRUCTIONS (follow these strictly when using each tool):\n\n" . implode("\n\n", $lines);
    }

    private function getCaseInstructions(?ChatAgent $chatAgent): string
    {
        if (!Schema::hasTable('forbidden_behaviours')) {
            return '';
        }

        $query = ForbiddenBehaviour::query()->where('is_active', true);

        if ($chatAgent) {
            $query->where('chat_agent_id', $chatAgent->id);
        } else {
            $query->whereNull('chat_agent_id');
        }

        $rules = $query->orderByRaw("FIELD(level, 'danger', 'warning', 'info')")->get();

        if ($rules->isEmpty()) {
            return '';
        }

        $lines = ['FORBIDDEN BEHAVIOURS (strictly prohibited — never violate):'];

        foreach ($rules as $rule) {
            $levelTag = strtoupper($rule->level);
            $lines[] = "- [{$levelTag}] {$rule->instruction}";
        }

        return implode("\n", $lines);
    }

    private function getBotName(): string
    {
        if (!Schema::hasTable('tools')) {
            return 'xoneBot';
        }

        $config = Tool::query()->where('tool_name', '_bot_config')->first();

        return trim((string) ($config?->meta['bot_name'] ?? 'xoneBot')) ?: 'xoneBot';
    }
}
