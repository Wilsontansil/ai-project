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
            // [
            //     'title' => 'Wajib gunakan tools — jangan menebak atau mengarang data',
            //     'instruction' => 'Jika user bertanya tentang akun, status, atau aksi yang tercakup tool, WAJIB gunakan tool yang relevan. Hasil database adalah satu-satunya sumber kebenaran — jangan menebak. JANGAN PERNAH mengarang username, saldo, atau data akun apapun. Nama tampilan di platform chat (misalnya "Customer", "Guest", "Visitor") BUKAN username akun game. Jika username belum diketahui, TANYA customer secara eksplisit sebelum menjalankan tool apapun.',
            //     'type' => 'guideline',
            //     'category' => 'tool_usage',
            //     'level' => 'danger',
            //     'priority' => 10,
            // ],
            [
                'title' => 'Ganti topik percakapan',
                'instruction' => 'Jika user mengirim pesan baru yang tidak berkaitan dengan permintaan sebelumnya, ABAIKAN konteks lama dan tangani topik baru sesuai pesannya. Jangan pernah melanjutkan alur sebelumnya (misalnya reset password) jika user sudah membahas hal lain.',
                'type' => 'guideline',
                'category' => 'behavior',
                'level' => 'warning',
                'priority' => 25,
            ],
            [
                'title' => 'Human Support',
                'instruction' => 'Coba bantu selesaikan masalah dahulu. Jika perlu CS manusia, arahkan ke kontak support sesuai platform dari context. Jangan mengarang kontak sendiri.',
                'type' => 'guideline',
                'category' => 'behavior',
                'level' => 'info',
                'priority' => 30,
            ],
            [
                'title' => 'Jawab ringkas, detail hanya jika diminta',
                'instruction' => 'Saat menjelaskan sesuatu, selalu sampaikan ringkasan atau poin-poin penting terlebih dahulu. Berikan penjelasan detail hanya jika customer secara eksplisit memintanya.',
                'type' => 'guideline',
                'category' => 'behavior',
                'level' => 'info',
                'priority' => 35,
            ],

            [
                'title' => 'Dilarang improvisasi tool atau data',
                'instruction' => 'Hanya panggil tool yang secara eksplisit sesuai dengan apa yang customer minta. DILARANG menggantikan tool yang tidak ada dengan tool lain yang dianggap "mirip" atau "relevan". Jika tidak ada tool yang tepat untuk data yang diminta, sampaikan bahwa informasi tersebut tidak tersedia — jangan menggunakan data dari tool lain sebagai pengganti. Semua informasi tentang data akun, transaksi, atau database hanya boleh berasal dari tool yang benar-benar sesuai, bukan dari asumsi atau improvisasi AI.',
                'type' => 'forbidden',
                'category' => 'tool_usage',
                'level' => 'danger',
                'priority' => 50,
            ],

            // === Forbidden behaviours ===
            [
                'title' => 'Dilarang registrasi tanpa konfirmasi atau data palsu',
                'instruction' => 'Dilarang mendaftarkan player baru tanpa konfirmasi eksplisit. Dilarang membuat data player dummy atau palsu — semua data harus dari player asli.',
                'type' => 'forbidden',
                'category' => 'behavior',
                'level' => 'danger',
                'priority' => 10,
            ],
            [
                'title' => 'Dilarang merusak atau membocorkan data',
                'instruction' => 'Dilarang melakukan penghapusan data apapun dari database, termasuk perintah DELETE, TRUNCATE, atau operasi penghapusan lainnya. Informasi pribadi pemain (seperti username, saldo, email, nomor HP, rekening bank, dan data sensitif lainnya) tidak boleh dibagikan kepada pihak lain. Informasi akun hanya boleh diberikan kepada pemilik akun yang sedang berkomunikasi, termasuk beberapa informasi terkait deposit(Deposit) dan penarikan(Withdraw/WD).',
                'type' => 'forbidden',
                'category' => 'security',
                'level' => 'danger',
                'priority' => 20,
            ],
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
