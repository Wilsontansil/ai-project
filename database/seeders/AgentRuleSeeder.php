<?php

namespace Database\Seeders;

use App\Models\AgentRule;
use App\Models\ChatAgent;
use Illuminate\Database\Seeder;

class AgentRuleSeeder extends Seeder
{
    public function run(): void
    {
        // Remove only known seeder-managed rules before re-seeding (preserves user-created rules)
        AgentRule::query()->whereIn('title', [
            'Request Ganti Rekening Player / User',
            'Charge Transfer Kesalahan (Pulsa ↔ E-Wallet)',
            'Panduan Menjawab Data RTP & Pola Gacor',
            'Transfer ke Rekening Web Nonaktif',
            'Dilarang registrasi tanpa konfirmasi atau data palsu',
            'Dilarang membagikan data pribadi pemain',
            'Analisa Gambar',
            'Dilarang merusak atau membocorkan data',
        ])->delete();

        // Load agents by type for targeted assignment
        $agents = ChatAgent::query()->pluck('id', 'agent_type');
        $triageId   = $agents['triage']  ?? null;
        $akunId     = $agents['account'] ?? null;
        $bayarId    = $agents['payment'] ?? null;
        $gameId     = $agents['game']    ?? null;
        $bonusId    = $agents['bonus']   ?? null;

        // Rules specific to each agent — keyed by agent id
        $agentRules = [

            // ── Agent Akun ────────────────────────────────────────────
            $akunId => [
                [
                    'title' => 'Request Ganti Rekening Player / User',
                    'instruction' => 'Hanya jika user meminta secara spesifik menganti rekening user.
                Jika user meminta ganti rekening, tanyakan alasan terlebih dahulu.
Hanya lanjut jika alasan terkait kesalahan data rekening (salah nomor rekening/norek atau salah nama rekening/namarek).
Jika alasan valid, kumpulkan data rekening lama dan baru: namarek, norek, bank, namarek_new, norek_new.
Jika alasan di luar koreksi kesalahan data, permintaan ganti rekening tidak diperbolehkan.',
                    'type' => 'guideline',
                    'category' => 'behavior',
                    'level' => 'info',
                    'priority' => 80,
                ],
                [
                    'title' => 'Dilarang registrasi tanpa konfirmasi atau data palsu',
                    'instruction' => 'Dilarang mendaftarkan player baru tanpa konfirmasi eksplisit. Dilarang membuat data player dummy atau palsu — semua data harus dari player asli.',
                    'type' => 'forbidden',
                    'category' => 'behavior',
                    'level' => 'danger',
                    'priority' => 10,
                ],
            ],

            // ── Agent Pembayaran ───────────────────────────────────────
            $bayarId => [
                [
                    'title' => 'Charge Transfer Kesalahan (Pulsa ↔ E-Wallet)',
                    'instruction' => 'Jika terjadi kesalahan transfer, seperti transfer pulsa ke Dana atau sebaliknya, infokan ke player bahwa akan dikenakan biaya admin Rp 5.000.
Prosedur:
- Konfirmasi dengan empati bahwa kesalahan transfer terjadi.
- Jelaskan bahwa charge 5.000 akan diberlakukan dan ditanggung oleh player.
- Minta bukti transfer asli (screenshot struk/mutasi bank atau e-wallet).
- Segera eskalasi ke Human Support dengan menyertakan bukti transfer tersebut.
- JANGAN janjikan refund atau pembatalan charge — keputusan ada di Human Support.
- Deposit wajib menggunakan rekening asli dengan nama yang sama seperti yang terdaftar di profil akun.
- Jika terdapat pertanyaan perbedaan nama rekening antara rekening yang digunakan dengan data akun saat melakukan deposit, arahkan pemain untuk menggunakan metode QRIS terlebih dahulu.',
                    'type' => 'guideline',
                    'category' => 'behavior',
                    'level' => 'info',
                    'priority' => 75,
                ],
                [
                    'title' => 'Transfer ke Rekening Web Nonaktif',
                    'instruction' => 'Jika player melaporkan sudah melakukan transfer (deposit) ke nomor rekening / e-wallet (contoh: Dana, OVO, dll.) yang sebelumnya aktif, tetapi saat mengisi form deposit nomor tersebut sudah tidak aktif atau tidak tersedia lagi:

1. JANGAN meminta player mengisi ulang form dengan nomor berbeda secara mandiri — ini berisiko deposit tidak terproses.
2. Konfirmasi dengan empati bahwa situasi ini memerlukan penanganan manual.
3. Minta player menyiapkan:
   - Bukti transfer (screenshot struk/mutasi)
   - Nominal dan waktu transfer
   - Nomor rekening/e-wallet tujuan yang digunakan
4. Segera eskalasi ke Human Support dengan menyampaikan semua detail di atas.
5. JANGAN menjanjikan dana akan dikembalikan atau diproses otomatis — proses verifikasi dilakukan oleh Human Support.',
                    'type' => 'guideline',
                    'category' => 'behavior',
                    'level' => 'warning',
                    'priority' => 90,
                ],
            ],

            // ── Agent Game ────────────────────────────────────────────
            $gameId => [
                [
                    'title' => 'Panduan Menjawab Data RTP & Pola Gacor',
                    'instruction' => 'Ketika user bertanya tentang RTP, pola gacor, atau slot game tertentu, gunakan data dari Knowledge Base yang bersumber dari website (tipe: website scrape).

Aturan menjawab:
1. Jika ditemukan, jawab dengan format lengkap , detail dan format yang rapi:
   - Nama Game & Provider
   - RTP (persentase)
   - Jam Gacor
   - Pola Gacor (tampilkan step-by-step)
   - Nominal Bet (jika tersedia)
2. Jika game yang ditanya TIDAK ADA di KB, jawab dengan jujur: "Data untuk game [nama] tidak tersedia di sumber kami saat ini." — JANGAN mengarang atau mengira-ngira pola/RTP.
3. Jika user bertanya secara umum ("slot gacor hari ini", "rekomendasi slot"), sebutkan 3–5 game dengan RTP tertinggi dari KB.
4. JANGAN menyebutkan nama website sumber (domain scrape) kepada user.
5. Selalu Utamakan ["PRAGMATIC PLAY","PG SOFT"] di Urutan 1 dan 2.
6. Juga Berikan {rtp_url} dari System Config',
                    'type' => 'guideline',
                    'category' => 'behavior',
                    'level' => 'info',
                    'priority' => 60,
                ],
            ],
        ];

        // Rules applied to ALL agents (security & tool-usage policies)
        $globalRules = [
            // === Guideline (aturan operasional / keamanan) ===
            [
                'title' => 'Request Ganti Rekening Player / User',
                'instruction' => 'Hanya jika user meminta secara spesifik menganti rekening user.
                Jika user meminta ganti rekening, tanyakan alasan terlebih dahulu.
Hanya lanjut jika alasan terkait kesalahan data rekening (salah nomor rekening/norek atau salah nama rekening/namarek).
Jika alasan valid, kumpulkan data rekening lama dan baru: namarek, norek, bank, namarek_new, norek_new.
Jika alasan di luar koreksi kesalahan data, permintaan ganti rekening tidak diperbolehkan.',
                'type' => 'guideline',
                'category' => 'behavior',
                'level' => 'info',
                'priority' => 80,
            ],
            [
                'title' => 'Charge Transfer Kesalahan (Pulsa ↔ E-Wallet)',
                'instruction' => 'Jika terjadi kesalahan transfer, seperti transfer pulsa ke Dana atau sebaliknya, infokan ke player bahwa akan dikenakan biaya admin Rp 5.000.
Prosedur:
- Konfirmasi dengan empati bahwa kesalahan transfer terjadi.
- Jelaskan bahwa charge 5.000 akan diberlakukan dan ditanggung oleh player.
- Minta bukti transfer asli (screenshot struk/mutasi bank atau e-wallet).
- Segera eskalasi ke Human Support dengan menyertakan bukti transfer tersebut.
- JANGAN janjikan refund atau pembatalan charge — keputusan ada di Human Support.
- Deposit wajib menggunakan rekening asli dengan nama yang sama seperti yang terdaftar di profil akun.
- Jika terdapat pertanyaan perbedaan nama rekening antara rekening yang digunakan dengan data akun saat melakukan deposit, arahkan pemain untuk menggunakan metode QRIS terlebih dahulu.',
                'type' => 'guideline',
                'category' => 'behavior',
                'level' => 'info',
                'priority' => 75,
            ],
        ];

        // Rules applied to ALL agents (security & tool-usage policies)
        $globalRules = [
            [
                'title' => 'Dilarang membagikan data pribadi pemain',
                'instruction' => 'DILARANG KERAS membagikan informasi :
- saldo / balance
- username
- nomor HP
- nama rekening
- nama bank,
dan data sensitif lainnya.

Tidak perlu lagi menanyak informasi lanjut jika user / customer menanyakan hal diatas',
                'type' => 'forbidden',
                'category' => 'security',
                'level' => 'danger',
                'priority' => 50,
            ],
            [
                'title' => 'Analisa Gambar',
                'instruction' => 'Ketika user mengirimkan gambar, WAJIB gunakan tool analisa gambar yang tersedia.
DILARANG mendeskripsikan, menginterpretasi, atau menganalisa isi gambar secara bebas tanpa melalui tool.
Jika tool analisa gambar tidak tersedia atau tidak mendukung jenis gambar tersebut, informasikan kepada user bahwa analisa tidak dapat dilakukan.',
                'type' => 'forbidden',
                'category' => 'tool_usage',
                'level' => 'danger',
                'priority' => 80,
            ],
            [
                'title' => 'Dilarang merusak atau membocorkan data',
                'instruction' => 'Dilarang melakukan penghapusan data apapun dari database, termasuk perintah DELETE, TRUNCATE, atau operasi penghapusan lainnya.',
                'type' => 'forbidden',
                'category' => 'security',
                'level' => 'danger',
                'priority' => 100,
            ],
        ];

        // Seed per-agent rules
        foreach ($agentRules as $agentId => $rules) {
            if ($agentId === null) {
                continue;
            }
            $agent = \App\Models\ChatAgent::find($agentId);
            if ($agent === null) {
                continue;
            }
            foreach ($rules as $rule) {
                $existing = AgentRule::query()->where('title', $rule['title'])->first();
                if ($existing === null) {
                    $existing = AgentRule::query()->create(array_merge($rule, ['chat_agent_id' => null, 'is_active' => true]));
                }
                $agent->agentRules()->syncWithoutDetaching([$existing->id]);
            }
        }

        // Seed global rules to every agent
        $allAgentIds = array_filter([$triageId, $akunId, $bayarId, $gameId, $bonusId]);
        foreach ($globalRules as $rule) {
            $existing = AgentRule::query()->where('title', $rule['title'])->first();
            if ($existing === null) {
                $existing = AgentRule::query()->create(array_merge($rule, ['chat_agent_id' => null, 'is_active' => true]));
            }
            foreach ($allAgentIds as $agentId) {
                $agent = \App\Models\ChatAgent::find($agentId);
                $agent?->agentRules()->syncWithoutDetaching([$existing->id]);
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
