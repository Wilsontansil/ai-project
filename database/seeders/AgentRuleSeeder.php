<?php

namespace Database\Seeders;

use App\Models\AgentRule;
use App\Models\ChatAgent;
use Illuminate\Database\Seeder;

class AgentRuleSeeder extends Seeder
{
    public function run(): void
    {
        $defaultAgent = ChatAgent::query()->where('is_default', true)->first();
        $agentId = $defaultAgent?->id;

        $rules = [
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
            // [
            //     'title' => 'Wajib gunakan tools — jangan menebak atau mengarang data',
            //     'instruction' => 'Jika user bertanya tentang akun, status, atau aksi yang tercakup tool, WAJIB gunakan tool yang relevan. Hasil database adalah satu-satunya sumber kebenaran — jangan menebak. JANGAN PERNAH mengarang username, saldo, atau data akun apapun. Nama tampilan di platform chat (misalnya "Customer", "Guest", "Visitor") BUKAN username akun game. Jika username belum diketahui, TANYA customer secara eksplisit sebelum menjalankan tool apapun.',
            //     'type' => 'guideline',
            //     'category' => 'tool_usage',
            //     'level' => 'danger',
            //     'priority' => 10,
            // ],
            // [
            //     'title' => 'Ganti topik percakapan',
            //     'instruction' => 'Jika user mengirim pesan baru yang tidak berkaitan dengan permintaan sebelumnya, ABAIKAN konteks lama dan tangani topik baru sesuai pesannya. Jangan pernah melanjutkan alur sebelumnya (misalnya reset password) jika user sudah membahas hal lain.',
            //     'type' => 'guideline',
            //     'category' => 'behavior',
            //     'level' => 'warning',
            //     'priority' => 25,
            // ],
            // === Forbidden behaviours ===
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
            [
                'title' => 'Dilarang registrasi tanpa konfirmasi atau data palsu',
                'instruction' => 'Dilarang mendaftarkan player baru tanpa konfirmasi eksplisit. Dilarang membuat data player dummy atau palsu — semua data harus dari player asli.',
                'type' => 'forbidden',
                'category' => 'behavior',
                'level' => 'danger',
                'priority' => 10,
            ],
            [
                'title' => 'Dilarang membagikan data pribadi pemain',
                'instruction' =>  'DILARANG KERAS membagikan informasi :
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
//             [
//                 'title' => 'Tidak Bisa Diganti',
//                 'instruction' => 'Hal yang tidak bisa diganti:
//                 Jika customer meminta mengubah data rekening, yaitu:
// - Nama Rekening (namarek)
// - Nama Bank / Bank (bank)
// - Nomor Rekening (norek)

// Data tersebut TIDAK BISA DIGANTI setelah pendaftaran. TOLAK permintaan perubahan secara tegas dan sopan.',
// // Arahkan customer untuk mendaftar akun baru jika ingin menggunakan rekening berbeda. Jangan menawarkan alternatif lain.',
//                 'type' => 'forbidden',
//                 'category' => 'security',
//                 'level' => 'danger',
//                 'priority' => 50,
//             ],
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
            // [
            //     'title' => 'Human Support',
            //     'instruction' => 'DILARANG menyebutkan nomor telepon, email, livechat, atau kontak CS eksternal apapun. Platform ini tidak memiliki kontak CS selain sistem eskalasi internal. Jika tidak bisa menyelesaikan masalah, gunakan jalur eskalasi internal — JANGAN arahkan customer ke platform atau channel lain.',
            //     'type' => 'forbidden',
            //     'category' => 'behavior',
            //     'level' => 'danger',
            //     'priority' => 5,
            // ],
        ];

        foreach ($rules as $rule) {
            AgentRule::query()->updateOrCreate(
                ['title' => $rule['title']],
                array_merge($rule, [
                    'chat_agent_id' => $agentId,
                    'is_active' => true,
                ])
            );
        }

        // Assign any orphaned rules to the default agent
        if ($agentId) {
            AgentRule::query()
                ->whereNull('chat_agent_id')
                ->update(['chat_agent_id' => $agentId]);
        }
    }
}
