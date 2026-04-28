<?php

namespace Database\Seeders;

use App\Models\ChatAgent;
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
- Tetap profesional dengan user yang marah/kasar — balas dengan sopan, tambahkan emoji untuk melunakkan nada.
- Jangan Menjawab pertanyaan lain diluar konteks.',
                'model' => 'gpt-4.1-mini',
                'max_tokens' => 420,
                'temperature' => 0.7,
                'message_await_seconds' => 2,
                'is_enabled' => true,
                'is_default' => true,
                // 'escalation_condition' => 'Coba bantu selesaikan masalah customer terlebih dahulu.
                // Eskalasikan ke Human CS jika: deposit tidak masuk lebih dari 15 menit dan sudah dicek statusnya,
                // akun bermasalah (suspend/banned),
                // reset password kondisi balance diatas 10000 dan tidak dapat diselesaikan oleh bot, customer secara eksplisit meminta berbicara dengan manusia, atau masalah teknis lain yang tidak bisa diselesaikan bot. 
                // JANGAN PERNAH menyuruh customer menghubungi livechat atau CS melalui website/aplikasi lain — gunakan [ESCALATE] untuk meneruskan ke Human CS',
                'escalation_condition' => '
                Bot wajib mencoba membantu dan melakukan pengecekan awal terhadap masalah customer sebelum melakukan eskalasi ke human support.

                CASE yang harus Human Support

                1. Kesalahan Data Rekening
                * Salah nomor / nama rekening
                → Wajib verifikasi KTP + selfie dengan KTP
                * Nomor rekening sudah terdaftar
                → Indikasi duplikat akun, keputusan oleh SPV
                * Salah kategori rekening (contoh: DANA dipilih sebagai bank)
                → Dibantu SPV untuk koreksi kategori

                2. Masalah Deposit
                * Deposit pihak ke-3 (rekening berbeda dari akun)
                → Penyelesaian manual melalui bank
                * Deposit QRIS tidak masuk
                → Cek manual ke vendor (kemungkinan gangguan)
                * Deposit tidak masuk lebih dari 5 menit dan status sudah dicek
                → Lanjutkan pengecekan manual / eskalasi ke human support

                3. Masalah Withdraw (WD)
                * WD sudah approve tapi belum masuk
                → Cek limit rekening dan kesalahan data rekening

                4. Masalah Bonus
                * Klaim bonus (freespin / buyspin)
                → Cek manual sesuai syarat & ketentuan

                * Klaim bonus winstreak sabung ayam
                → Cek manual sesuai syarat & ketentuan

                5. Masalah Saldo / Game
                * Saldo hilang (contoh: Spaceman)
                → Cek manual ke CS engine

                6. Masalah Akun
                * Akun bermasalah (Suspend / Banned)
                → Perlu pengecekan dan keputusan dari tim terkait / SPV

                7. Kondisi Wajib Eskalasi
                * Bot tidak dapat menyelesaikan masalah
                * Membutuhkan pengecekan manual (vendor / bank / engine)
                * Membutuhkan keputusan khusus (SPV)

                Catatan:
                * Bot harus memberikan penjelasan yang jelas dan relevan ke user
                * Bot harus tetap fokus pada penyelesaian masalah user',

                'timezone' => 'Asia/Jakarta',
                'stop_ai_after_handoff' => false,
                'silent_handoff' => false,
            ]
        );

    }
}
