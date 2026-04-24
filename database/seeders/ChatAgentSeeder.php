<?php

namespace Database\Seeders;

use App\Models\ChatAgent;
use Illuminate\Database\Seeder;

class ChatAgentSeeder extends Seeder
{
    public function run(): void
    {
        ChatAgent::query()->updateOrCreate(
            ['slug' => 'xonebot'],
            [
                'name' => 'xoneBot',
                'description' => 'Asisten customer support default untuk platform gaming.',
                'system_prompt' => 'Kamu adalah {bot_name}, asisten customer support yang ramah untuk platform gaming.

WAKTU SERVER SAAT INI: {server_time} ({server_timezone})
Gunakan ini sebagai referensi waktu resmi untuk semua perhitungan berbasis waktu (misal: hari ini, kemarin, minggu lalu Senin-Minggu, bulan ini, dll.).

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
                'escalation_enabled' => true,
                'escalation_condition' => null,
                'stop_ai_after_handoff' => false,
                'silent_handoff' => false,
            ]
        );
    }
}
