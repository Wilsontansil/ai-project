<?php

namespace App\Services\AI;

use App\Models\ChatAgent;
use App\Models\AgentRule;
use App\Models\KnowledgeBase;
use App\Models\Tool;
use App\Models\WebsitePage;
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

        $basePrompt .= "\n\n" . $this->getWebsitePagesPrompt();

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
PANDUAN PENGGUNAAN TOOLS (ikuti dengan ketat):
Kamu memiliki akses ke KNOWLEDGE BASE dan TOOLS. Ikuti urutan ini sebelum memutuskan:

1. JAWAB DARI KNOWLEDGE BASE terlebih dahulu jika pertanyaan bersifat umum/informatif.
   Contoh: "apa itu cashback?", "kapan cashback dibagikan?", "bagaimana cara hitung cashback?"
   → Jawab langsung dari Knowledge Base. JANGAN panggil tool.
   → Ikuti juga panduan nada dan call-to-action yang ada di dalam entri Knowledge Base yang digunakan.

2. PANGGIL TOOL hanya jika customer membutuhkan DATA SPESIFIK miliknya sendiri.
   Contoh: "cek cashback saya", "berapa cashback saya minggu ini?", "lihat histori deposit saya"
   → Ini memerlukan data real-time dari sistem. Panggil tool yang sesuai.

3. KOMBINASI: Jika customer bertanya umum DAN ingin cek datanya sekaligus,
   jawab penjelasan singkat dari Knowledge Base DULU, lalu panggil tool untuk datanya.

4. GANTI TOPIK: Jika customer memulai permintaan BARU (menggunakan kata seperti "mau",
   "minta", "ingin", "cek", "info tentang", "tolong bantu", dll.) yang TIDAK berkaitan
   dengan tool atau data yang sedang diproses sebelumnya, JAWAB permintaan baru tersebut.
   Anggap alur percakapan sebelumnya sudah selesai dan mulai dari konteks baru.

PENTING — ALUR DATA MULTI-TURN:

FASE 1 — PEMICU AWAL:
Jika customer menyebutkan intent untuk menggunakan tool (contoh: "mau reset password",
"minta withdraw", "daftar akun"), SEGERA panggil tool yang sesuai — meskipun data belum
lengkap. Sistem akan otomatis mendeteksi data yang kurang dan meminta ke customer.
JANGAN jawab hanya dengan teks jika ada tool yang cocok dengan intent customer.

FASE 2 — SETELAH MEMINTA DATA:
Jika kamu sudah meminta data spesifik ke customer (misal: nama rekening, nomor rekening,
nama bank) dan customer membalas dengan data tersebut, JANGAN panggil tool lagi.
Sistem akan mengambil data itu dan menjalankan tool secara otomatis.
Tugasmu hanya menjaga percakapan tetap fokus jika customer keluar topik.

PRINSIP UTAMA:
- Jangan panggil tool hanya karena pertanyaan menyebut kata kunci tool.
- Pertimbangkan konteks: apakah customer butuh PENJELASAN atau butuh DATA?
- Jika ragu apakah customer menjawab pertanyaanmu atau membuat permintaan baru, lihat apakah pesan mereka mengandung kata permintaan ("mau", "minta", "tolong") atau hanya data murni (nama, angka, dll.).
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
                    $content = (string) $entry->content;
                }
                $lines[] = "### {$entry->title}\n{$content}";
            }

            return implode("\n\n", $lines);
        } catch (\Throwable) {
            return '';
        }
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

    private function getWebsitePagesPrompt(): string
    {
        try {
            $pages = WebsitePage::where('status', 'scraped')
                ->orderBy('id')
                ->get(['url', 'title']);

            if ($pages->isEmpty()) {
                return "HALAMAN WEBSITE RESMI KAMI:\nSaat ini tidak ada URL website yang terdaftar. Jika customer bertanya tentang website atau link, sampaikan bahwa informasi URL belum tersedia.";
            }

            $lines = [
                'HALAMAN WEBSITE RESMI KAMI:',
                'Halaman-halaman berikut adalah milik website resmi kami sendiri. Kamu BOLEH dan HARUS membagikan URL ini ke customer kapanpun relevan — ini bukan website pihak ketiga, jadi tidak perlu ragu.',
            ];

            foreach ($pages as $page) {
                $label = $page->title ?: $page->url;
                $lines[] = "- {$label}: {$page->url}";
            }

            return implode("\n", $lines);
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
}
