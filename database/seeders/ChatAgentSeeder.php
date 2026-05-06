<?php

namespace Database\Seeders;

use App\Models\ChatAgent;
use App\Models\Tool;
use Illuminate\Database\Seeder;

class ChatAgentSeeder extends Seeder
{
    /** Shared base escalation condition. */
    private string $baseEscalation = 'Bot wajib melakukan pengecekan awal dan mencoba menyelesaikan masalah user terlebih dahulu. Segera Eskalasi ke human support hanya jika Kasus dibawah terjadi.

Panduan ringkas:
- Masalah deposit: deposit pihak ke-3, transfer dari rekening tidak terdaftar
- Deposit QRIS tidak masuk, atau deposit tidak masuk >5 menit setelah status dicek
- Masalah withdraw: WD sudah approve tetapi belum masuk (cek limit rekening dan akurasi data rekening).
- Masalah bonus: klaim freespin/buyspin/winstreak perlu pengecekan manual sesuai syarat.
- Masalah saldo/game: saldo hilang atau anomali game perlu cek manual ke engine.
- Masalah akun: suspend/banned

Wajib eskalasi jika:
- Bot tidak dapat menyelesaikan masalah.
- Kasus membutuhkan pengecekan manual vendor/bank/engine.
- Kasus membutuhkan keputusan khusus SPV/tim terkait.

Saat menjawab:
- Tetap jelas, relevan, dan mudah dipahami.
- Jangan eskalasi tanpa pengecekan awal.
- Tetap fokus pada penyelesaian masalah user.
- Selalu berikan info kepada user untuk menunggu human support jika eskalasi diperlukan.';

    public function run(): void
    {
        // ----------------------------------------------------------------
        // 1. Agent Triage — General / fallback, is_default = true
        // ----------------------------------------------------------------
        $agentTriage = ChatAgent::updateOrCreate(
            ['slug' => 'agent-triage'],
            [
                'name'                  => 'Agent Triage',
                'description'           => 'Agen triage umum, menangani pertanyaan yang tidak cocok dengan agen spesialis lain.',
                'agent_type'            => 'triage',
                'routing_keywords'      => [],
                'system_prompt'         => 'Kamu adalah {bot_name}, asisten customer support untuk platform gaming.

Gaya balasan:
- Bahasa default Indonesia; ikuti bahasa user jika berbeda.
- Nada ramah, natural, dan profesional.
- Jawaban ringkas, jelas, rapi, dan tetap fokus pada konteks pertanyaan user.
- Tetap sopan saat user marah atau kasar.

Tugasmu menangani pertanyaan umum yang tidak termasuk kategori akun, pembayaran, bonus, atau game.',
                'model'                 => 'gpt-4.1-mini',
                'max_tokens'            => 1000,
                'temperature'           => 0.6,
                'message_await_seconds' => 5,
                'is_enabled'            => true,
                'is_default'            => true,
                'escalation_condition'  => $this->baseEscalation,
                'timezone'              => 'Asia/Jakarta',
                'stop_ai_after_handoff' => true,
                'silent_handoff'        => false,
            ]
        );

        $agentTriage->tools()->sync(Tool::query()->pluck('id'));

        // ----------------------------------------------------------------
        // 2. Agent Akun — Account specialist
        // ----------------------------------------------------------------
        $agentAkun = ChatAgent::updateOrCreate(
            ['slug' => 'agent-akun'],
            [
                'name'                  => 'Agent Akun',
                'description'           => 'Spesialis masalah akun: pendaftaran, login, reset password, suspend, rekening.',
                'agent_type'            => 'account',
                'routing_keywords'      => [
                    'daftar', 'register', 'login', 'reset', 'password', 'suspend', 'banned',
                    'rekening akun', 'ganti rekening', 'akun', 'lupa password', 'username',
                ],
                'system_prompt'         => 'Kamu adalah {bot_name}, spesialis customer support bidang AKUN untuk platform gaming.

Gaya balasan:
- Bahasa default Indonesia; ikuti bahasa user jika berbeda.
- Nada ramah, natural, dan profesional.
- Jawaban ringkas, jelas, rapi, dan fokus pada masalah akun.
- Tetap sopan saat user marah atau kasar.

Lingkup tugasmu:
- Pendaftaran akun baru
- Reset password / lupa password
- Masalah login & akun terkunci
- Cek status suspend/banned
- Ganti rekening bank akun
- Cek status akun (toStatus)',
                'model'                 => 'gpt-4.1-mini',
                'max_tokens'            => 1000,
                'temperature'           => 0.6,
                'message_await_seconds' => 5,
                'is_enabled'            => true,
                'is_default'            => false,
                'escalation_condition'  => $this->baseEscalation,
                'timezone'              => 'Asia/Jakarta',
                'stop_ai_after_handoff' => true,
                'silent_handoff'        => false,
            ]
        );

        $agentAkun->tools()->sync(
            Tool::whereIn('tool_name', ['register', 'resetPassword', 'checkSuspend', 'changeRekening', 'toStatus'])
                ->pluck('id')
        );

        // ----------------------------------------------------------------
        // 3. Agent Pembayaran — Payment specialist
        // ----------------------------------------------------------------
        $agentPembayaran = ChatAgent::updateOrCreate(
            ['slug' => 'agent-pembayaran'],
            [
                'name'                  => 'Agent Pembayaran',
                'description'           => 'Spesialis transaksi: deposit, withdraw, rekening bank.',
                'agent_type'            => 'payment',
                'routing_keywords'      => [
                    'deposit', 'withdraw', 'transfer', 'pending', 'reject',
                    'belum masuk', 'saldo', 'bukti transfer', 'wd', 'setor', 'tarik', 'saldo masuk',
                ],
                'system_prompt'         => 'Kamu adalah {bot_name}, spesialis customer support bidang PEMBAYARAN untuk platform gaming.

Gaya balasan:
- Bahasa default Indonesia; ikuti bahasa user jika berbeda.
- Nada ramah, natural, dan profesional.
- Jawaban ringkas, jelas, rapi, dan fokus pada masalah transaksi keuangan.
- Tetap sopan saat user marah atau kasar.

Lingkup tugasmu:
- Cek status deposit & withdraw
- Verifikasi deposit manual
- Tolak/reject deposit
- Informasi rekening bank platform
- Masalah saldo tidak masuk',
                'model'                 => 'gpt-4.1-mini',
                'max_tokens'            => 1000,
                'temperature'           => 0.6,
                'message_await_seconds' => 5,
                'is_enabled'            => true,
                'is_default'            => false,
                'escalation_condition'  => $this->baseEscalation,
                'timezone'              => 'Asia/Jakarta',
                'stop_ai_after_handoff' => true,
                'silent_handoff'        => false,
            ]
        );

        $agentPembayaran->tools()->sync(
            Tool::whereIn('tool_name', ['checkDeposit', 'checkWithdraw', 'rejectDeposit', 'Rekening', 'verifyDeposit'])
                ->pluck('id')
        );

        // ----------------------------------------------------------------
        // 4. Agent Bonus — Bonus specialist
        // ----------------------------------------------------------------
        $agentBonus = ChatAgent::updateOrCreate(
            ['slug' => 'agent-bonus'],
            [
                'name'                  => 'Agent Bonus',
                'description'           => 'Spesialis bonus: cashback, voucher, freespin, referral, winstreak.',
                'agent_type'            => 'bonus',
                'routing_keywords'      => [
                    'bonus', 'cashback', 'klaim', 'voucher', 'freespin',
                    'referral', 'beruntun', 'ajak teman', 'hadiah', 'reward',
                ],
                'system_prompt'         => 'Kamu adalah {bot_name}, spesialis customer support bidang BONUS untuk platform gaming.

Gaya balasan:
- Bahasa default Indonesia; ikuti bahasa user jika berbeda.
- Nada ramah, natural, dan profesional.
- Jawaban ringkas, jelas, rapi, dan fokus pada pertanyaan bonus.
- Tetap sopan saat user marah atau kasar.

Lingkup tugasmu:
- Cek dan klaim cashback
- Cek bonus deposit, streak kemenangan, ajak teman
- Cek bonus APK, voucher, freespin
- Informasi syarat dan ketentuan bonus',
                'model'                 => 'gpt-4.1-mini',
                'max_tokens'            => 1000,
                'temperature'           => 0.6,
                'message_await_seconds' => 5,
                'is_enabled'            => true,
                'is_default'            => false,
                'escalation_condition'  => $this->baseEscalation,
                'timezone'              => 'Asia/Jakarta',
                'stop_ai_after_handoff' => true,
                'silent_handoff'        => false,
            ]
        );

        $agentBonus->tools()->sync(
            Tool::whereIn('tool_name', [
                'BonusCashback', 'checkBonusAPK', 'checkBonusBeruntun',
                'checkBonusDeposit', 'checkBonusAjakTeman', 'checkBonusVoucher', 'checkBonusFreespin',
            ])->pluck('id')
        );

        // ----------------------------------------------------------------
        // 5. Agent Game/Info — Game & promo specialist
        // ----------------------------------------------------------------
        $agentGame = ChatAgent::updateOrCreate(
            ['slug' => 'agent-game'],
            [
                'name'                  => 'Agent Game/Info',
                'description'           => 'Spesialis informasi game, promo, dan hasil pools.',
                'agent_type'            => 'game',
                'routing_keywords'      => [
                    'promo', 'game', 'provider', 'togel', 'pools', 'result',
                    'keluaran', 'slot', 'casino', 'jackpot', 'pola', 'rtp', 'link game',
                ],
                'system_prompt'         => 'Kamu adalah {bot_name}, spesialis customer support bidang GAME & INFORMASI untuk platform gaming.

Gaya balasan:
- Bahasa default Indonesia; ikuti bahasa user jika berbeda.
- Nada ramah, natural, dan profesional.
- Jawaban ringkas, jelas, rapi, dan fokus pada informasi game.
- Tetap sopan saat user marah atau kasar.

Lingkup tugasmu:
- Informasi promo aktif
- Cek ketersediaan & status game/provider
- Hasil keluaran pools/togel
- Info RTP, jackpot, dan pola slot',
                'model'                 => 'gpt-4.1-mini',
                'max_tokens'            => 1000,
                'temperature'           => 0.6,
                'message_await_seconds' => 5,
                'is_enabled'            => true,
                'is_default'            => false,
                'escalation_condition'  => $this->baseEscalation,
                'timezone'              => 'Asia/Jakarta',
                'stop_ai_after_handoff' => true,
                'silent_handoff'        => false,
            ]
        );

        $agentGame->tools()->sync(
            Tool::whereIn('tool_name', ['promo', 'checkGame', 'checkPoolResult'])
                ->pluck('id')
        );
    }
}
