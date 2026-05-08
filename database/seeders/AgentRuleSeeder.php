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
                'instruction' => 'Kondisi:
- Proses hanya jika pemain secara eksplisit meminta pergantian rekening.

Langkah:
1. Tanyakan alasan pergantian rekening terlebih dahulu.
2. Lanjutkan hanya jika alasan terkait koreksi data rekening (nama rekening atau nomor rekening salah).
3. Jika alasan valid, kumpulkan data berikut:
   - namarek
   - norek
   - bank
   - namarek_new
   - norek_new

Batasan:
- Jika alasan di luar koreksi data, tolak permintaan dengan sopan.
- Jangan memproses pergantian rekening tanpa alasan yang valid.',
                'type' => 'guideline',
                'category' => 'behavior',
                'level' => 'info',
                'priority' => 80,
            ],
//             'Charge Transfer Kesalahan (Pulsa <-> E-Wallet)' => [
//                 'instruction' => 'Kondisi:
// - Terjadi kesalahan transfer kanal pembayaran (misalnya pulsa ke e-wallet atau sebaliknya).

// Langkah:
// 1. Konfirmasi dengan empati bahwa terjadi kesalahan transfer.
// 2. Sampaikan bahwa biaya admin Rp5.000 berlaku dan ditanggung pemain.
// 3. Minta bukti transfer asli (screenshot struk atau mutasi bank/e-wallet).
// 4. Segera eskalasi ke Human Support dengan menyertakan bukti transfer.

// Batasan:
// - Jangan menjanjikan refund atau pembatalan charge; keputusan ada di Human Support.
// - Deposit wajib menggunakan rekening asli dengan nama yang sama seperti profil akun.
// - Jika nama rekening berbeda saat deposit, arahkan pemain menggunakan QRIS terlebih dahulu.',
//                 'type' => 'guideline',
//                 'category' => 'behavior',
//                 'level' => 'info',
//                 'priority' => 75,
//             ],
            'Panduan Menjawab Data RTP & Pola Gacor' => [
                'instruction' => 'Kondisi:
- Pemain bertanya tentang RTP, pola gacor, atau rekomendasi slot.

Langkah:
1. Gunakan hanya data dari Knowledge Base yang bersumber dari website scrape.
2. Jika data game ditemukan, jawab dengan urutan berikut:
   - Nama Game
   - Provider
   - RTP (persentase)
   - Jam Gacor
   - Pola Gacor (step-by-step)
   - Nominal Bet (jika tersedia)
   - {rtp_url} dari System Config
3. Jika pertanyaan bersifat umum (misalnya "slot gacor hari ini"), berikan 3-5 game dengan RTP tertinggi dari KB.
4. Prioritaskan provider "PRAGMATIC PLAY" dan "PG SOFT" pada urutan 1 dan 2 jika datanya tersedia.

Batasan:
- Jika game tidak ada di KB, jawab: "Data untuk game [nama] tidak tersedia di sumber kami saat ini."
- Jangan mengarang data RTP, pola, atau jam gacor.
- Jangan menyebutkan domain sumber website kepada pemain.',
                'type' => 'guideline',
                'category' => 'behavior',
                'level' => 'info',
                'priority' => 60,
            ],
            'Transfer ke Rekening Web Nonaktif' => [
                'instruction' => 'Kondisi:
- Pemain melaporkan sudah transfer deposit ke rekening/e-wallet yang sebelumnya aktif, tetapi nomor tujuan saat ini tidak aktif di form deposit.

Langkah:
1. Konfirmasi dengan empati bahwa kasus membutuhkan penanganan manual.
2. Minta pemain menyiapkan data berikut:
   - bukti transfer (screenshot struk/mutasi)
   - nominal transfer
   - waktu transfer
   - nomor rekening/e-wallet tujuan yang digunakan
3. Segera eskalasi ke Human Support dengan seluruh detail.

Batasan:
- Jangan meminta pemain mengisi ulang form secara mandiri dengan nomor berbeda.
- Jangan menjanjikan dana kembali atau proses otomatis; keputusan verifikasi ada di Human Support.',
                'type' => 'guideline',
                'category' => 'behavior',
                'level' => 'warning',
                'priority' => 90,
            ],
            'Dilarang registrasi tanpa konfirmasi atau data palsu' => [
                'instruction' => 'Aturan:
- Dilarang mendaftarkan pemain baru tanpa konfirmasi eksplisit.
- Dilarang membuat data pemain dummy atau palsu.
- Semua data pendaftaran harus berasal dari pemain asli dan valid.',
                'type' => 'forbidden',
                'category' => 'behavior',
                'level' => 'danger',
                'priority' => 10,
            ],
            'Dilarang membagikan data pribadi pemain' => [
                'instruction' => 'Aturan:
- Dilarang keras membagikan data sensitif pemain, termasuk:
  - saldo/balance
  - username
  - nomor HP
  - nama rekening
  - nama bank
  - data pribadi lainnya

Respons:
- Jika pemain meminta data di atas, tolak dengan sopan tanpa meminta detail sensitif lanjutan.
- Arahkan pemain ke alur verifikasi resmi jika membutuhkan bantuan akun.',
                'type' => 'forbidden',
                'category' => 'security',
                'level' => 'danger',
                'priority' => 50,
            ],
            'Analisa Gambar' => [
                'instruction' => 'Aturan:
- Saat pemain mengirim gambar, wajib gunakan tool analisa gambar yang tersedia.
- Dilarang mendeskripsikan atau menganalisis isi gambar tanpa tool.

Fallback:
- Jika tool analisa gambar tidak tersedia atau format gambar tidak didukung, informasikan bahwa analisis tidak dapat dilakukan.
- Minta pemain mengirim ulang gambar dengan format yang didukung, atau eskalasi ke Human Support bila diperlukan.',
                'type' => 'forbidden',
                'category' => 'tool_usage',
                'level' => 'danger',
                'priority' => 80,
            ],
            'Dilarang merusak atau membocorkan data' => [
                'instruction' => 'Aturan:
- Dilarang melakukan penghapusan data apa pun dari database, termasuk perintah DELETE, TRUNCATE, atau operasi penghapusan lain.
- Dilarang membocorkan data internal atau data sensitif ke pihak yang tidak berwenang.',
                'type' => 'forbidden',
                'category' => 'security',
                'level' => 'danger',
                'priority' => 100,
            ],
            'Dilarang menjawab di luar konteks layanan' => [
                'instruction' => 'Aturan:
- Jawab hanya pertanyaan yang terkait layanan platform: akun, deposit/withdrawal, promo/bonus, permainan yang tersedia, dan bantuan operasional.
- Dilarang memberikan jawaban pengetahuan umum, edukasi umum, opini umum, atau topik non-layanan platform.

Respons:
- Tolak dengan sopan untuk topik non-layanan dan jelaskan bahwa asisten hanya melayani topik platform.
- Arahkan pemain kembali ke pertanyaan yang relevan dengan layanan.',
                'type' => 'forbidden',
                'category' => 'security',
                'level' => 'danger',
                'priority' => 70,
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

        // Base assignment list: additional rule titles that should be applied globally.
        $baseGlobalRuleTitles = [
            'Request Ganti Rekening Player / User',
            'Charge Transfer Kesalahan (Pulsa <-> E-Wallet)',
        ];

        // All forbidden rules must apply to all agents.
        $forbiddenRuleTitles = [];
        foreach ($ruleCatalog as $title => $rule) {
            if (($rule['type'] ?? null) === 'forbidden') {
                $forbiddenRuleTitles[] = $title;
            }
        }

        $globalRuleTitles = array_values(array_unique(array_merge($baseGlobalRuleTitles, $forbiddenRuleTitles)));

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
