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
                'title' => 'DEPOSIT',
                'instruction' => 'Jika terjadi kesalahan transfer, seperti transfer pulsa ke Dana atau sebaliknya, infokan ke player bahwa akan dikenakan biaya admin 5000.
- Deposit wajib menggunakan rekening asli dengan nama yang sama seperti yang terdaftar di profil akun.
- Jika terdapat pertanyaan perbedaan nama rekening antara rekening yang digunakan dengan data akun saat melakukan deposit, arahkan pemain untuk menggunakan metode QRIS terlebih dahulu.
- Alternatif lainnya, bantu pemain untuk melakukan pendaftaran.',
                'type' => 'guideline',
                'category' => 'behavior',
                'level' => 'info',
                'priority' => 50,
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
            [
                'title' => 'Tidak Bisa Diganti',
                'instruction' => 'Hal yang tidak bisa diganti:
                Jika customer meminta mengubah data rekening, yaitu:
- Nama Rekening (namarek)
- Nama Bank / Bank (bank)
- Nomor Rekening (norek)

Data tersebut TIDAK BISA DIGANTI setelah pendaftaran. TOLAK permintaan perubahan secara tegas dan sopan.',
// Arahkan customer untuk mendaftar akun baru jika ingin menggunakan rekening berbeda. Jangan menawarkan alternatif lain.',
                'type' => 'forbidden',
                'category' => 'security',
                'level' => 'danger',
                'priority' => 50,
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
