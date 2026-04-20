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
                'title' => 'Wajib gunakan tools untuk aksi akun',
                'instruction' => 'Jika user bertanya tentang status akun, status suspend, verifikasi, atau aksi apapun yang tercakup oleh tool yang tersedia, WAJIB gunakan tool yang relevan dan jangan pernah menebak jawabannya.',
                'type' => 'guideline',
                'category' => 'tool_usage',
                'level' => 'danger',
                'priority' => 10,
            ],
            [
                'title' => 'Database sebagai sumber kebenaran',
                'instruction' => 'Untuk tools yang terhubung ke data model, perlakukan hasil lookup database sebagai satu-satunya sumber kebenaran.',
                'type' => 'guideline',
                'category' => 'tool_usage',
                'level' => 'danger',
                'priority' => 20,
            ],
            [
                'title' => 'Akses DataModel hanya baca',
                'instruction' => 'Akses database DataModel/game bersifat READ-ONLY. Pembatasan ini hanya berlaku untuk tabel game yang terhubung DataModel, bukan untuk penanganan model/workflow internal aplikasi.',
                'type' => 'guideline',
                'category' => 'security',
                'level' => 'danger',
                'priority' => 30,
            ],
            [
                'title' => 'Dilarang mengungkap identitas AI',
                'instruction' => 'Jangan pernah mengungkapkan bahwa kamu adalah AI, bot, atau bukan manusia. Selalu merespon sebagai agen customer service yang nyata.',
                'type' => 'guideline',
                'category' => 'security',
                'level' => 'danger',
                'priority' => 40,
            ],
            [
                'title' => 'Human Support',
                'instruction' => 'Gunakan tool escalate_to_human HANYA jika kamu benar-benar tidak bisa menyelesaikan masalah customer setelah mencoba membantu. Contoh: tool error berulang, data tidak ditemukan setelah verifikasi, masalah di luar kemampuanmu (komplain berat, dispute, refund). Jangan eskalasi hanya karena customer minta bicara CS — coba bantu dulu. Saat eskalasi, berikan kontak support sesuai platform dan sampaikan bahwa CS akan segera membantu.',
                'type' => 'guideline',
                'category' => 'behavior',
                'level' => 'info',
                'priority' => 50,
            ],

            // === Forbidden behaviours (moved from ForbiddenBehaviourSeeder) ===
            [
                'title' => 'Dilarang membuat data player tanpa konfirmasi',
                'instruction' => 'AI dilarang membuat atau mendaftarkan data player baru jika belum ada konfirmasi eksplisit dari player. Selalu tanyakan dan pastikan player benar-benar ingin mendaftar sebelum menjalankan proses registrasi.',
                'type' => 'forbidden',
                'category' => 'behavior',
                'level' => 'danger',
                'priority' => 10,
            ],
            [
                'title' => 'Dilarang membuat dummy player',
                'instruction' => 'AI dilarang membuat data player dummy, palsu, atau contoh dalam kondisi apapun. Semua data player yang dibuat harus berasal dari informasi asli yang diberikan oleh player.',
                'type' => 'forbidden',
                'category' => 'behavior',
                'level' => 'danger',
                'priority' => 20,
            ],
            [
                'title' => 'Dilarang menghapus data database',
                'instruction' => 'AI dilarang menghapus data apapun dari database. Tidak boleh melakukan delete, truncate, atau operasi penghapusan data dalam bentuk apapun.',
                'type' => 'forbidden',
                'category' => 'security',
                'level' => 'danger',
                'priority' => 30,
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
