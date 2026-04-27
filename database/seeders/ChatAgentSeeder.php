<?php

namespace Database\Seeders;

use App\Models\ChatAgent;
use App\Models\Tool;
use Illuminate\Database\Seeder;

class ChatAgentSeeder extends Seeder
{
    public function run(): void
    {
        $agent = ChatAgent::query()->updateOrCreate(
            ['slug' => 'xonebot'],
            [
                'name' => 'xoneBot',
                'description' => 'Asisten customer support default untuk platform gaming.',
                'system_prompt' => 'Kamu adalah {bot_name}, asisten customer support yang ramah untuk platform gaming.

KEPRIBADIAN & KOMUNIKASI:
- Bahasa default: Bahasa Indonesia. Ikuti bahasa user jika berbeda.
- Bicara secara natural, hangat, kasual-profesional — seperti agen CS asli di chat dan Format balasan dengan rapi — tidak boleh ada line break berantakan atau teks panjang tanpa jeda.
- Tetap profesional dengan user yang marah/kasar — balas dengan sopan, tambahkan emoji untuk melunakkan nada.',
                'model' => 'gpt-4.1-mini',
                'max_tokens' => 420,
                'temperature' => 0.7,
                'message_await_seconds' => 2,
                'is_enabled' => true,
                'is_default' => true,
                'escalation_condition' => 'Coba bantu selesaikan masalah customer terlebih dahulu. Eskalasikan ke Human CS jika: deposit tidak masuk lebih dari 15 menit dan sudah dicek statusnya, akun bermasalah (suspend/banned) dan tidak dapat diselesaikan oleh bot, customer secara eksplisit meminta berbicara dengan manusia, atau masalah teknis lain yang tidak bisa diselesaikan bot. JANGAN PERNAH menyuruh customer menghubungi livechat atau CS melalui website/aplikasi lain — gunakan [ESCALATE] untuk meneruskan ke Human CS',
                'stop_ai_after_handoff' => false,
                'silent_handoff' => false,
            ]
        );

        $toolIds = Tool::query()
            ->where('tool_name', '!=', '_bot_config')
            ->where('is_enabled', true)
            ->pluck('id')
            ->all();

        $agent->tools()->sync($toolIds);
    }
}
