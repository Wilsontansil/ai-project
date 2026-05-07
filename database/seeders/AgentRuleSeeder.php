<?php

namespace Database\Seeders;

use App\Models\AgentRule;
use App\Models\ChatAgent;
use Illuminate\Database\Seeder;

class AgentRuleSeeder extends Seeder
{
    public function run(): void
    {
        // Single source of truth for prompt/rule definitions.
        $ruleCatalog = [
            'Request Ganti Rekening Player / User' => [
                'instruction' => 'Hanya proses jika pengguna secara spesifik meminta mengganti rekening.
Jika ada permintaan ganti rekening, tanyakan alasan terlebih dahulu.
Lanjutkan hanya jika alasan terkait koreksi data rekening (salah nomor rekening atau salah nama rekening).
Jika alasan valid, kumpulkan data rekening lama dan baru: namarek, norek, bank, namarek_new, norek_new.
Jika alasan di luar koreksi data, permintaan ganti rekening tidak diperbolehkan.',
                'type' => 'guideline',
                'category' => 'behavior',
                'level' => 'info',
                'priority' => 80,
            ],
            'Charge Transfer Kesalahan (Pulsa <-> E-Wallet)' => [
                'instruction' => 'Jika terjadi kesalahan transfer (misalnya pulsa ke Dana atau sebaliknya), informasikan bahwa akan dikenakan biaya admin Rp5.000.
Prosedur:
- Konfirmasi dengan empati bahwa terjadi kesalahan transfer.
- Jelaskan bahwa biaya admin Rp5.000 diberlakukan dan ditanggung pemain.
- Minta bukti transfer asli (screenshot struk/mutasi bank atau e-wallet).
- Segera eskalasi ke Human Support dengan menyertakan bukti transfer.
- Jangan menjanjikan refund atau pembatalan charge; keputusan ada di Human Support.
- Deposit wajib menggunakan rekening asli dengan nama yang sama seperti profil akun.
- Jika ada perbedaan nama rekening saat deposit, arahkan pemain menggunakan metode QRIS terlebih dahulu.',
                'type' => 'guideline',
                'category' => 'behavior',
                'level' => 'info',
                'priority' => 75,
            ],
            'Panduan Menjawab Data RTP & Pola Gacor' => [
                'instruction' => 'Saat pengguna bertanya tentang RTP, pola gacor, atau slot tertentu, gunakan data dari Knowledge Base yang bersumber dari website (tipe: website scrape).
Aturan menjawab:
1. Jika data ditemukan, jawab dalam format rapi:
   - Nama Game & Provider
   - RTP (persentase)
   - Jam Gacor
   - Pola Gacor (step-by-step)
   - Nominal Bet (jika tersedia)
2. Jika game tidak ada di KB, jawab jujur: "Data untuk game [nama] tidak tersedia di sumber kami saat ini." Jangan mengarang data RTP/pola.
3. Jika pertanyaan umum (mis. "slot gacor hari ini"), berikan 3-5 game dengan RTP tertinggi dari KB.
4. Jangan menyebutkan nama website sumber (domain scrape) kepada pengguna.
5. Prioritaskan "PRAGMATIC PLAY" dan "PG SOFT" pada urutan 1 dan 2.
6. Sertakan {rtp_url} dari System Config.',
                'type' => 'guideline',
                'category' => 'behavior',
                'level' => 'info',
                'priority' => 60,
            ],
            'Transfer ke Rekening Web Nonaktif' => [
                'instruction' => 'Jika pemain melaporkan sudah transfer deposit ke rekening/e-wallet yang sebelumnya aktif, tetapi nomor tersebut sudah tidak aktif saat pengisian form:
1. Jangan meminta pemain mengisi ulang form dengan nomor berbeda secara mandiri.
2. Konfirmasi dengan empati bahwa kasus ini memerlukan penanganan manual.
3. Minta pemain menyiapkan:
   - Bukti transfer (screenshot struk/mutasi)
   - Nominal dan waktu transfer
   - Nomor rekening/e-wallet tujuan yang digunakan
4. Segera eskalasi ke Human Support dengan seluruh detail di atas.
5. Jangan menjanjikan dana kembali atau proses otomatis; verifikasi dilakukan Human Support.',
                'type' => 'guideline',
                'category' => 'behavior',
                'level' => 'warning',
                'priority' => 90,
            ],
            'Dilarang registrasi tanpa konfirmasi atau data palsu' => [
                'instruction' => 'Dilarang mendaftarkan pemain baru tanpa konfirmasi eksplisit.
Dilarang membuat data pemain dummy atau palsu.
Semua data harus berasal dari pemain asli.',
                'type' => 'forbidden',
                'category' => 'behavior',
                'level' => 'danger',
                'priority' => 10,
            ],
            'Dilarang membagikan data pribadi pemain' => [
                'instruction' => 'Dilarang keras membagikan data sensitif, termasuk:
- saldo/balance
- username
- nomor HP
- nama rekening
- nama bank
- data pribadi lainnya.
Jika pengguna meminta data di atas, tolak dengan sopan tanpa meminta detail lanjutan.',
                'type' => 'forbidden',
                'category' => 'security',
                'level' => 'danger',
                'priority' => 50,
            ],
            'Analisa Gambar' => [
                'instruction' => 'Saat pengguna mengirim gambar, wajib gunakan tool analisa gambar yang tersedia.
Dilarang mendeskripsikan atau menganalisa isi gambar tanpa tool.
Jika tool analisa gambar tidak tersedia atau tidak mendukung format gambar, informasikan bahwa analisa tidak dapat dilakukan.',
                'type' => 'forbidden',
                'category' => 'tool_usage',
                'level' => 'danger',
                'priority' => 80,
            ],
            'Dilarang merusak atau membocorkan data' => [
                'instruction' => 'Dilarang melakukan penghapusan data apa pun dari database, termasuk DELETE, TRUNCATE, atau operasi penghapusan lainnya.',
                'type' => 'forbidden',
                'category' => 'security',
                'level' => 'danger',
                'priority' => 100,
            ],
        ];

        // Remove only seeder-managed rules before re-seeding (preserves user-created custom rules).
        AgentRule::query()->whereIn('title', array_keys($ruleCatalog))->delete();

        // Load agents by type for targeted assignment.
        $agents = ChatAgent::query()->pluck('id', 'agent_type');
        $triageId = $agents['triage'] ?? null;
        $akunId = $agents['account'] ?? null;
        $bayarId = $agents['payment'] ?? null;
        $gameId = $agents['game'] ?? null;
        $bonusId = $agents['bonus'] ?? null;

        // Assignment map: which rule titles belong to each specific agent.
        $agentRuleAssignments = [
            $akunId => [
                'Request Ganti Rekening Player / User',
                'Dilarang registrasi tanpa konfirmasi atau data palsu',
            ],
            $bayarId => [
                'Charge Transfer Kesalahan (Pulsa <-> E-Wallet)',
                'Transfer ke Rekening Web Nonaktif',
            ],
            $gameId => [
                'Panduan Menjawab Data RTP & Pola Gacor',
            ],
        ];

        // Assignment list: rule titles that should be applied globally to all agents.
        $globalRuleTitles = [
            'Request Ganti Rekening Player / User',
            'Charge Transfer Kesalahan (Pulsa <-> E-Wallet)',
            'Dilarang membagikan data pribadi pemain',
            'Analisa Gambar',
            'Dilarang merusak atau membocorkan data',
        ];

        // Cache created/loaded rules by title to avoid duplicate DB lookups.
        $ruleByTitle = [];
        $resolveRule = function (string $title) use ($ruleCatalog, &$ruleByTitle): ?AgentRule {
            if (! isset($ruleCatalog[$title])) {
                return null;
            }

            if (isset($ruleByTitle[$title])) {
                return $ruleByTitle[$title];
            }

            $existing = AgentRule::query()->where('title', $title)->first();
            if ($existing === null) {
                $existing = AgentRule::query()->create(array_merge(
                    ['title' => $title],
                    $ruleCatalog[$title],
                    ['chat_agent_id' => null, 'is_active' => true]
                ));
            }

            $ruleByTitle[$title] = $existing;

            return $existing;
        };

        // Assign prompt/rules to specific agents.
        foreach ($agentRuleAssignments as $agentId => $titles) {
            if ($agentId === null) {
                continue;
            }

            $agent = ChatAgent::query()->find($agentId);
            if ($agent === null) {
                continue;
            }

            foreach ($titles as $title) {
                $rule = $resolveRule($title);
                if ($rule === null) {
                    continue;
                }

                $agent->agentRules()->syncWithoutDetaching([$rule->id]);
            }
        }

        // Assign global prompt/rules to all agents.
        $allAgentIds = array_filter([$triageId, $akunId, $bayarId, $gameId, $bonusId]);
        foreach ($globalRuleTitles as $title) {
            $rule = $resolveRule($title);
            if ($rule === null) {
                continue;
            }

            foreach ($allAgentIds as $agentId) {
                $agent = ChatAgent::query()->find($agentId);
                $agent?->agentRules()->syncWithoutDetaching([$rule->id]);
            }
        }

        // Triage gets every rule that exists
        if ($triageId !== null) {
            $triageAgent = \App\Models\ChatAgent::find($triageId);
            if ($triageAgent !== null) {
                $allRuleIds = AgentRule::query()->pluck('id')->toArray();
                $triageAgent->agentRules()->syncWithoutDetaching($allRuleIds);
            }
        }
    }
}
