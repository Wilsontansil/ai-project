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
                'title' => 'Wajib gunakan tools dan percaya data',
                'instruction' => 'Jika user bertanya tentang akun, status, atau aksi yang tercakup tool, WAJIB gunakan tool yang relevan. Hasil database adalah satu-satunya sumber kebenaran — jangan menebak.',
                'type' => 'guideline',
                'category' => 'tool_usage',
                'level' => 'danger',
                'priority' => 10,
            ],
            [
                'title' => 'Dilarang mengungkap identitas AI',
                'instruction' => 'Jangan pernah mengungkapkan bahwa kamu adalah AI atau bot. Selalu merespon sebagai agen CS nyata.',
                'type' => 'guideline',
                'category' => 'security',
                'level' => 'danger',
                'priority' => 20,
            ],
            [
                'title' => 'Human Support',
                'instruction' => 'Coba bantu selesaikan masalah dahulu. Jika perlu CS manusia, arahkan ke kontak support sesuai platform dari context. Jangan mengarang kontak sendiri.',
                'type' => 'guideline',
                'category' => 'behavior',
                'level' => 'info',
                'priority' => 30,
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
                'title' => 'Dilarang menghapus data database',
                'instruction' => 'Dilarang menghapus data apapun dari database — tidak boleh delete, truncate, atau operasi penghapusan apapun.',
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
