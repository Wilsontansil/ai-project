<?php

namespace App\Services\AI;

use App\Models\ChatAgent;
use App\Models\AgentRule;
use App\Models\KnowledgeBase;
use App\Models\SystemConfig;
use App\Models\Tool;
use App\Services\AI\KnowledgeBaseQueryGuard;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $now = now();
        $timezone = trim((string) ($chatAgent?->timezone ?? ''));

        if ($timezone !== '') {
            try {
                $now = $now->setTimezone($timezone);
            } catch (\Throwable) {
                // Keep app default timezone if stored timezone is invalid.
            }
        }

        $serverTime = $now->format('Y-m-d H:i:s (l)');
        $serverTimezone = $now->getTimezone()->getName();

        if ($chatAgent && !empty($chatAgent->system_prompt)) {
            $basePrompt = str_replace(
                ['{bot_name}', '{server_time}', '{server_timezone}'],
                [$botName, $serverTime, $serverTimezone],
                $chatAgent->system_prompt
            );
        } else {
            $basePrompt = "Kamu adalah {$botName}, asisten customer support yang ramah untuk platform gaming.";
        }

        $basePrompt .= "\n\n" . $this->getTimeReferencePrompt($serverTime, $serverTimezone);

        $agentRulesPrompt = $this->getAgentRulesPrompt($chatAgent);
        if ($agentRulesPrompt !== '') {
            $basePrompt .= "\n\n" . $agentRulesPrompt;
        }

        $basePrompt .= "\n\n" . $this->getToolUsagePolicyPrompt();

        $kbPrompt = $this->getKnowledgeBasePrompt($chatAgent);
        if ($kbPrompt !== '') {
            $basePrompt .= "\n\n" . $kbPrompt;
        }

        $escalationPrompt = $this->getEscalationPrompt($chatAgent);
        if ($escalationPrompt !== '') {
            $basePrompt .= "\n\n" . $escalationPrompt;
        }

        // Tool rules are intentionally NOT included here to reduce payload size.
        // They are injected per-tool in the second OpenAI call (generateReplyFromToolResult)
        // via $toolContext['tool_rules'], where they actually matter.

        return $basePrompt;
    }

    private function getTimeReferencePrompt(string $serverTime, string $serverTimezone): string
    {
        return "WAKTU ACUAN SAAT INI: {$serverTime} ({$serverTimezone})\nGunakan ini sebagai referensi waktu resmi untuk semua perhitungan berbasis waktu (misal: hari ini, kemarin, minggu lalu Senin-Minggu, bulan ini, dll.).";
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
PANDUAN PENGGUNAAN TOOLS:
Kamu punya akses ke KNOWLEDGE BASE dan TOOLS. Gunakan aturan prioritas berikut:

1. Pertanyaan umum/informatif: jawab dari Knowledge Base, jangan panggil tool.
2. Permintaan data spesifik milik customer: panggil tool yang relevan.
3. Pertanyaan gabungan (umum + data user): jawab singkat dari Knowledge Base dulu, lalu panggil tool.
4. Jika customer memulai permintaan baru yang tidak terkait konteks sebelumnya, anggap konteks lama selesai dan tangani permintaan baru.

ALUR MULTI-TURN:
- Fase intent awal: jika ada intent jelas untuk aksi tool, segera panggil tool meskipun data belum lengkap. Sistem akan meminta data yang kurang.
- Fase setelah minta data: jika customer sedang melengkapi data yang diminta, jangan panggil tool lagi dari pesan itu; sistem akan mengeksekusi otomatis.

PRINSIP:
- Jangan memilih tool hanya karena keyword; nilai kebutuhan user: penjelasan atau data.
- Jika ragu antara "jawaban data" vs "permintaan baru", anggap permintaan baru hanya bila user jelas meminta aksi/topik baru.

// ATURAN KHUSUS BONUS VS PROMO:
// - Jika user menanyakan bonus secara umum (contoh: "bonus apa saja", "bonus apa aja", "info bonus", "cara klaim bonus", "bonus cashback", "bonus referral", "bonus deposit"), WAJIB jawab dari Knowledge Base bagian "Bonus" dan JANGAN panggil tool promo.
// - Tool promo HANYA untuk pertanyaan promo aktif/event/banner/promosi yang sedang berjalan (contoh: "promo aktif", "daftar promo", "event terbaru", "promo hari ini").
// - Jika user menanyakan bonus dan promo sekaligus, jawab bonus dari Knowledge Base "Bonus" terlebih dahulu, lalu lanjutkan info promo aktif menggunakan tool promo.
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
                if ($entry->source === 'datamodel') {
                    $content = $this->resolveDataModelKbContent($entry);
                } else {
                    $content = $this->resolveConfigPlaceholders((string) $entry->content);
                }
                $lines[] = "### {$entry->title}\n{$content}";
            }

            return implode("\n\n", $lines);
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Replace {key} placeholders in KB content with values from SystemConfig.
     * If the description is a value-label map (e.g. "1=Senin, 2=Selasa, ..."),
     * the placeholder is replaced with the resolved label (e.g. "Senin").
     * Unknown keys are left as-is (no crash, no empty string substitution).
     */
    private function resolveConfigPlaceholders(string $content): string
    {
        return preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', function (array $m): string {
            $entry = SystemConfig::getValueWithDescription($m[1]);
            if ($entry['value'] === null) {
                return $m[0];
            }
            if ($entry['description'] !== null) {
                $label = $this->resolveDescriptionLabel($entry['value'], $entry['description']);
                if ($label !== null) {
                    return $label;
                }
            }
            return $entry['value'];
        }, $content) ?? $content;
    }

    /**
     * Parse a description like "1=Senin, 2=Selasa, 3=Rabu, ..." and return the label
     * matching $value. Returns null if the description is not in that format or no match found.
     */
    private function resolveDescriptionLabel(string $value, string $description): ?string
    {
        // Match patterns like: 1=Senin or 1 = Senin (comma/semicolon separated)
        preg_match_all('/([^,;=\s]+)\s*=\s*([^,;]+)/', $description, $matches, PREG_SET_ORDER);
        if (empty($matches)) {
            return null;
        }
        foreach ($matches as $match) {
            if (trim($match[1]) === trim($value)) {
                return trim($match[2]);
            }
        }
        return null;
    }

    /**
     * Execute the stored SQL query for a datamodel KB entry and return formatted result.
     * Result is cached for 5 minutes per entry to avoid hitting the external DB on every message.
     */
    private function resolveDataModelKbContent(KnowledgeBase $entry): string
    {
        if (empty($entry->query_sql)) {
            return '(no query configured)';
        }

        $cacheKey = "kb_datamodel:{$entry->id}:" . md5((string) $entry->query_sql);

        return Cache::remember($cacheKey, 300, function () use ($entry): string {
            try {
                $dataModel = $entry->dataModel;
                if ($dataModel === null) {
                    return '(data model not found)';
                }

                $connectionName = $dataModel->connection_name ?: 'mysqlgame';
                $rows = DB::connection($connectionName)->select($entry->query_sql);

                if (count($rows) > KnowledgeBaseQueryGuard::MAX_ROWS) {
                    Log::warning('KB datamodel query exceeded row limit, result truncated', [
                        'kb_id'     => $entry->id,
                        'row_count' => count($rows),
                        'limit'     => KnowledgeBaseQueryGuard::MAX_ROWS,
                    ]);
                    $rows = array_slice($rows, 0, KnowledgeBaseQueryGuard::MAX_ROWS);
                }

                if (empty($rows)) {
                    return '(no results)';
                }

                // Format as a readable list: one row per line, key: value pairs.
                $lines = [];
                foreach ($rows as $row) {
                    $parts = [];
                    foreach ((array) $row as $col => $val) {
                        $parts[] = "{$col}: {$val}";
                    }
                    $lines[] = implode(' | ', $parts);
                }

                return implode("\n", $lines);
            } catch (\Throwable $e) {
                Log::warning('KB datamodel query failed', [
                    'kb_id' => $entry->id,
                    'error' => $e->getMessage(),
                ]);

                return '(query error — check configuration)';
            }
        });
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

    private function getEscalationPrompt(?ChatAgent $chatAgent): string
    {
        $condition = trim((string) ($chatAgent?->escalation_condition ?? ''));
        if ($condition === '') {
            return '';
        }
        return "ALIH KE CS MANUSIA:\nCoba bantu selesaikan masalah customer terlebih dahulu. {$condition}, cukup tambahkan penanda tersembunyi persis di baris terakhir balasanmu: [ESCALATE] — tanpa spasi, tanpa teks tambahan setelahnya. Sistem akan otomatis mengirimkan pesan tunggu kepada customer. Gunakan [ESCALATE] setiap kali kondisi eskalasi terpenuhi — bahkan jika sudah pernah digunakan sebelumnya dalam percakapan ini.";
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
}
