<?php

namespace App\Services\AI;

use App\Models\ChatAgent;
use App\Models\AgentRule;
use App\Models\KnowledgeBase;
use App\Models\ProjectSetting;
use App\Models\Tool;

/**
 * Builds system prompts and agent context injections for OpenAI requests.
 *
 * Responsibilities:
 *   - Compose the full system prompt from ChatAgent config + agent rules + tool rules.
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
            $basePrompt = "Kamu adalah {$botName}, asisten customer support yang ramah untuk platform gaming.

        WAKTU SERVER SAAT INI: {$serverTime} ({$serverTimezone})
        Gunakan ini sebagai referensi waktu resmi untuk semua perhitungan berbasis waktu (misal: hari ini, kemarin, minggu lalu Senin-Minggu, bulan ini, dll.).
        ";
        }

        $agentRulesPrompt = $this->getAgentRulesPrompt($chatAgent);
        if ($agentRulesPrompt !== '') {
            $basePrompt .= "\n\n" . $agentRulesPrompt;
        }

        $basePrompt .= "\n\n" . $this->getToolUsagePolicyPrompt();

        $kbPrompt = $this->getKnowledgeBasePrompt($chatAgent);
        if ($kbPrompt !== '') {
            $basePrompt .= "\n\n" . $kbPrompt;
        }

        // Tool rules are intentionally NOT included here to reduce payload size.
        // They are injected per-tool in the second OpenAI call (generateReplyFromToolResult)
        // via $toolContext['tool_rules'], where they actually matter.

        return $basePrompt;
    }

    /**
     * Build the optional customer context block injected as a second system message.
     *
     * @param array<string, mixed> $context
     */
    public function buildAgentContextPrompt(array $context, string $channel = 'telegram'): ?string
    {
        if ($context === []) {
            return null;
        }

        $profile = (array) ($context['customer_profile'] ?? []);

        $parts = [
            'Konteks customer (internal saja — jangan ungkapkan ke user):',
            'Platform saat ini: ' . $channel,
        ];

        $supportContact = $this->getSupportContact($channel);
        if ($supportContact !== null) {
            $parts[] = 'Kontak human support untuk platform ini: ' . $supportContact;
        }

        if ($profile !== []) {
            $parts[] = 'Profil: ' . json_encode([
                'platform' => $profile['platform'] ?? null,
                'name' => $profile['name'] ?? null,
                'total_messages' => $profile['total_messages'] ?? null,
                'tags' => $profile['tags'] ?? [],
            ], JSON_UNESCAPED_UNICODE);
        }

        return implode("\n\n", $parts);
    }

    private function getToolUsagePolicyPrompt(): string
    {
        return <<<'PROMPT'
PANDUAN PENGGUNAAN TOOLS (ikuti dengan ketat):

Kamu memiliki akses ke KNOWLEDGE BASE dan TOOLS. Ikuti urutan ini sebelum memutuskan:

1. JAWAB DARI KNOWLEDGE BASE terlebih dahulu jika pertanyaan bersifat umum/informatif.
   Contoh: "apa itu cashback?", "kapan cashback dibagikan?", "bagaimana cara hitung cashback?"
   → Jawab langsung dari Knowledge Base. JANGAN panggil tool.

2. PANGGIL TOOL hanya jika customer membutuhkan DATA SPESIFIK miliknya sendiri.
   Contoh: "cek cashback saya", "berapa cashback saya minggu ini?", "lihat histori deposit saya"
   → Ini memerlukan data real-time dari sistem. Panggil tool yang sesuai.

3. KOMBINASI: Jika customer bertanya umum DAN ingin cek datanya sekaligus,
   jawab penjelasan singkat dari Knowledge Base DULU, lalu panggil tool untuk datanya.

PRINSIP UTAMA:
- Jangan panggil tool hanya karena pertanyaan menyebut kata kunci tool.
- Pertimbangkan konteks: apakah customer butuh PENJELASAN atau butuh DATA?
- Jika ragu, jawab dari Knowledge Base dulu dan tawarkan untuk cek data jika diperlukan.
PROMPT;
    }

    private function getKnowledgeBasePrompt(?ChatAgent $chatAgent): string
    {
        if ($chatAgent === null) {
            return '';
        }

        try {
            $entries = KnowledgeBase::query()
                ->where('chat_agent_id', $chatAgent->id)
                ->where('is_active', true)
                ->orderBy('id')
                ->get();

            if ($entries->isEmpty()) {
                return '';
            }
            $lines = ['KNOWLEDGE BASE (gunakan sebagai referensi tambahan):'];
            foreach ($entries as $entry) {
                $lines[] = "### {$entry->title}\n{$entry->content}";
            }

            return implode("\n\n", $lines);
        } catch (\Throwable) {
            return '';
        }
    }

    private function getAgentRulesPrompt(?ChatAgent $chatAgent): string
    {
        try {

        $query = AgentRule::query()->where('is_active', true);

        if ($chatAgent) {
            $query->where('chat_agent_id', $chatAgent->id);
        } else {
            $query->whereNull('chat_agent_id');
        }

        $allRules = $query->orderBy('priority')->get();

        // When escalation is disabled for this agent, strip any rule that teaches the AI to escalate.
        if ($chatAgent !== null && !($chatAgent->escalation_enabled ?? true)) {
            $allRules = $allRules->filter(fn ($rule) => !str_contains($rule->instruction, '[ESCALATE]'));
        }

        if ($allRules->isEmpty()) {
            return '';
        }

        $sections = [];

        // Guidelines
        $guidelines = $allRules->where('type', 'guideline');
        if ($guidelines->isNotEmpty()) {
            $lines = ['ATURAN (ikuti dengan ketat):'];
            foreach ($guidelines as $rule) {
                $tag = strtoupper($rule->category);
                $lines[] = "- [{$tag}] {$rule->instruction}";
            }
            $sections[] = implode("\n", $lines);
        }

        // Forbidden
        $forbidden = $allRules->where('type', 'forbidden');
        if ($forbidden->isNotEmpty()) {
            $lines = ['PERILAKU TERLARANG (dilarang keras — jangan pernah dilanggar):'];
            foreach ($forbidden as $rule) {
                $levelTag = strtoupper($rule->level);
                $lines[] = "- [{$levelTag}] {$rule->instruction}";
            }
            $sections[] = implode("\n", $lines);
        }

        return implode("\n\n", $sections);
        } catch (\Throwable) {
            return '';
        }
    }

    private function getBotName(): string
    {
        try {
            $config = Tool::query()->where('tool_name', '_bot_config')->first();

            return trim((string) ($config?->meta['bot_name'] ?? 'xoneBot')) ?: 'xoneBot';
        } catch (\Throwable) {
            return 'xoneBot';
        }
    }

    private function getSupportContact(string $channel): ?string
    {
        $map = [
            'telegram' => ProjectSetting::getValue('support_telegram_tag'),
            'whatsapp' => ProjectSetting::getValue('support_whatsapp_phone'),
            'livechat' => ProjectSetting::getValue('support_livechat_url'),
        ];

        $contact = $map[$channel] ?? null;

        return $contact !== null && $contact !== '' ? $contact : null;
    }
}
