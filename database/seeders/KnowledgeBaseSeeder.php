<?php

namespace Database\Seeders;

use App\Models\ChatAgent;
use App\Models\DataModel;
use App\Models\KnowledgeBase;
use Illuminate\Database\Seeder;

class KnowledgeBaseSeeder extends Seeder
{
    public function run(): void
    {
        $agents = ChatAgent::query()->pluck('id', 'agent_type');
        $triageId  = $agents['triage']  ?? null;
        $akunId    = $agents['account'] ?? null;
        $bayarId   = $agents['payment'] ?? null;
        $bonusId   = $agents['bonus']   ?? null;
        $gameId    = $agents['game']    ?? null;

        if (!$triageId && !$akunId && !$bayarId && !$bonusId && !$gameId) {
            return;
        }

        $providersDataModelId = DataModel::query()
            ->where('slug', 'providers')
            ->value('id');

        // Clean up seeder-managed entries before re-seeding
        KnowledgeBase::query()->whereIn('title', [
            'Pools', 'Bonus', 'Deposit', 'Sports', 'Togel Info',
            'Withdraw', 'Register', 'General', 'Link & Pola', 'Provider',
        ])->delete();

        $this->seed($triageId, [
            ['title' => 'General', 'source' => 'manual', 'content' => '=== GENERAL WEBSITE KNOWLEDGE ===

WEBSITE IDENTITY:
- Nama website utama: PGS
- Deskripsi: Situs game online terpercaya di Indonesia dengan sistem keamanan canggih, promo eksklusif, deposit QRIS cepat, dan layanan 24 jam nonstop
- Domain utama: {main_domain}

═══════════════════════════════════════════════════

INSTRUKSI AI - WEBSITE IDENTITY QUESTIONS:

Jika user bertanya: "Apa nama website?", "Mau main di mana?", "Website apa ini?", atau pertanyaan sejenis:
→ Jawab: "Website kami adalah PGS (website utama: {main_domain})"
→ Sertakan deskripsi singkat: keamanan canggih, promo eksklusif, QRIS cepat, 24 jam support

Jika user bertanya domain/link akses:
→ Selalu berikan URL resmi: {main_domain}
→ Jangan sarankan akses lewat situs lain tanpa otorisasi

═══════════════════════════════════════════════════

PARTNER SITES HANDLING:

Partner websites resmi: CMBET, BIGMSG, GSC11, IDXBIG
- Jika user menyebut salah satu dari list di atas:
  → Jawab: "Itu adalah web partner kami"
  → JANGAN promosikan sebagai pilihan utama
  → Jika member ingin tetap pakai, tidak masalah (tapi recommend PGS)

Situs lain yang tidak di-list:
- Jika user mention situs selain yang di-list:
  → Jawab: "Kami tidak memiliki relasi dengan situs tersebut"
  → JANGAN konfirmasi atau rekomendasikan
  → Redirect: "Jika ingin main, gunakan PGS resmi di {main_domain}"

═══════════════════════════════════════════════════

OUT-OF-SCOPE HANDLING:

Jika user bertanya tentang:
- Register → Arahkan ke knowledge "Register"
- Deposit/WD → Arahkan ke knowledge "Deposit" atau "Withdraw"
- Game/Provider → Arahkan ke knowledge "Provider"
- Bonus → Arahkan ke knowledge "Bonus"
- Jadwal Togel → Arahkan ke knowledge "Pools"
- Taruhan Sports → Arahkan ke knowledge "Sports"
- Lainnya di luar topik website → "Pertanyaan ini di luar scope saya. Ada yang bisa kami bantu seputar daftar, deposit, atau game? 😊"

═══════════════════════════════════════════════════

TONE:
- Ramah, welcoming
- Promote PGS sebagai pilihan utama, not push partner sites
- Direct clear, tidak ambiguous'],
        ]);

        $this->seed($akunId, [
            ['title' => 'Register', 'source' => 'manual', 'content' => '=== PANDUAN REGISTRASI MEMBER BARU ===

CORE RULES:
- Jawab sesuai pertanyaan user, bahasa ramah dan welcoming
- Jangan cek data member tanpa diminta
- JANGAN minta password — system otomatis generate password default (1234567)

═══════════════════════════════════════════════════

USER INTENTS & RESPONSES:

1. [INTENT: Tanya Info Situs]
   User: "Apa aja game di sini?", "Ada apa aja?", "Gimana ini situs?"
   → Jawab dengan showcase: "Kami punya slot, live casino, togel, sabung ayam, sports betting! Plus bonus member baru, promo harian, deposit QRIS cepat, WD cepat, support 24 jam. Mau saya bantu daftar? 😊"

2. [INTENT: Tanya Syarat Daftar]
   User: "Berapa syarat daftar?", "Apa saja yang dibutuhkan?", "Gimana cara daftar?"
   → Jawab: "Cukup siapkan 2 hal: Username pilihan Anda + Nomor rekening bank atau E-wallet (atas nama Anda atau sesuai profil). Saya bantu daftarnya! 😊"
   → CLARIFY E-WALLET: "Catatan: jika pakai E-wallet, pastikan akun sudah berstatus Premium untuk bisa withdraw nanti."
   → Tambah incentive: "Member baru juga dapet bonus first deposit!"
   → Tidak ada batasan umur atau syarat khusus lain.

3. [INTENT: Langsung Minta Daftar]
   User: "Saya mau daftar!", "Ayo daftar sekarang!"
   → Jawab: "Siap! Untuk registrasi, boleh Anda berikan: 1. Username yang Anda inginkan 2. Nomor rekening/E-wallet + nama pemilik rekening"
   → Collect data step-by-step (jangan minta semua sekaligus jika user kesulitan)

4. [INTENT: Sudah Punya Akun Tapi Lupa Password]
   User: "Lupa password", "Password saya salah", "Gimana reset password?"
   → Jawab: "Tidak masalah! Anda bisa reset password dengan bantuan admin. Silakan berikan username Anda, kemudian kami teruskan ke tool reset password untuk verifikasi."
   → Minta: username + nama rekening terdaftar + nomor rekening
   → Arahkan ke "Reset Password" tool (mention: "password akan direset ke default 1234567")

5. [INTENT: Out-of-Scope / Pertanyaan Lain]
   User: bertanya hal di luar registrasi (bonus, deposit, withdraw, dll)
   → Jawab: "Pertanyaan yang bagus! Tapi saya khusus membantu proses pendaftaran. Setelah akun aktif, teman admin lain siap bantu untuk [bonus/deposit/withdraw/game]. Ada yang ingin ditanyakan tentang daftar? 😊"

═══════════════════════════════════════════════════

IMPORTANT CLARIFICATIONS:

E-WALLET REQUIREMENTS:
✓ Boleh pakai E-wallet untuk DEPOSIT (tidak perlu Premium)
✗ E-wallet WAJIB Premium untuk WITHDRAW (catatan ini penting — member harus upgrade dulu)

REKENING REQUIREMENTS:
✓ Boleh nama rekening bukan atas nama member — yang penting nama sesuai profil akun
✓ Boleh rekening orang lain (misal orang tua, teman) — asal nama sesuai
✗ HARUS match dengan profil — jangan transfer dari rekening lain yang nama-nya beda

DEFAULT PASSWORD:
- Password otomatis: 1234567
- Jangan minta user set password custom saat register — system generate

NO UPSELLING:
- Jangan push bonus atau promosi berlebihan saat register
- Cukup mention "bonus member baru" sebagai incentive
- Detail bonus dijelaskan SETELAH member aktif

═══════════════════════════════════════════════════

TONE:
- Friendly, welcoming, encouraging
- Supportive (helpful, not pushy)
- Use emoji sparingly tapi warm (😊)'],
        ]);

        $this->seed($bayarId, [
            ['title' => 'Deposit', 'source' => 'manual', 'content' => '[Informasi]
- Minimal Deposit Rp {dep_min}
- Maximal Deposit Rp Tak terbatas
- Deposit Menggunakan Pulsa Wajib Beserta SN Atau Nomor HP pengirim di Berita Deposit
- Multiplier Deposit Bank = {dep_mul_bank}
- Multiplier Deposit Non Bank = {dep_mul_nonbank}

[BANK]
BCA, BII/Maybank, BNI, BRI, BSI, BTN, CIMB Niaga, Niaga Syariah, Dana, Danamon, Mandiri, OCBC NISP, Bank Neo, Sea Bank, Jago, Permata, BTPN/Jenius, Bank MAS, Mandiri Syariah, BCA Syariah, QRIS
(E-Wallet) = Ovo, Shopee, Gopay, LINK AJA, Dana

[NON BANK] (Pulsa) = XL, Telkomsel

Deposit Bank:
Deposit berbeda bank dikenakan biaya 6.500 atau 2.500 (BI-Fast). Nominal di form deposit harus SAMA PERSIS dengan nominal permintaan deposit.
Wajib menggunakan rekening pribadi dengan nama yang sama persis seperti yang terdaftar di profil akun.

Deposit QRIS: Tidak wajib rekening asli. Bisa jadi alternatif jika ada masalah perbedaan nama rekening.

Context: Jika member transfer beberapa kali untuk 1 deposit, cukup isi 1x form deposit dengan total gabungan.

Catatan: Tidak ada Refund. Jika terjadi kesalahan, arahkan ke Human Support.
Rekening Player = rekening terdaftar di akun player.
Rekening Web = rekening di website untuk proses deposit (lihat Tool Rekening).'],
            ['title' => 'Withdraw', 'source' => 'manual', 'content' => '=== PANDUAN WITHDRAWAL / PENARIKAN DANA ===

CORE RULES:
- Minimal Withdraw Rp {wd_min}
- Withdraw berkelipatan Rp 1000
- Turnover (TO) HARUS diselesaikan terlebih dahulu sebelum bisa WD
- Jika TO belum selesai → WD tidak bisa diproses

═══════════════════════════════════════════════════

INSTRUKSI AI - WITHDRAWAL REQUESTS:

User Intent Mapping:

1. [INTENT: Tanya Proses Withdraw]
   User: "Gimana cara withdraw?", "Mau tarik dana", "Kapan bisa withdraw?", "Proses withdraw berapa lama?"
   → Jawab langkah-langkah: "Prosesnya simpel! 1. Pastikan Turnover sudah selesai → 2. Pilih metode (E-wallet atau Bank) → 3. Isi form withdraw → 4. Tunggu proses (biasanya cepat)"
   → Clarify: "Minimal Rp {wd_min}, kelipatan Rp 1000. Pilih E-wallet atau bank sesuai mau. Ada biaya untuk beberapa metode, lihat detail di bawah"
   → Tawarkan: "Mau tahu tentang E-wallet, Bank Digital, atau ada masalah tertentu?"

2. [INTENT: E-Wallet vs Bank]
   User: "Pakai E-wallet atau bank?", "Mana yang lebih cepat?", "Biaya berapa?", "Withdraw pake apa yang bagus?"
   → Jawab comparison:
     - E-WALLET: Cepat, tapi HARUS Premium terlebih dahulu. Tidak ada biaya tambahan.
     - BANK DIGITAL (Jago, SeaBank, blu BCA, Neo, Jenius): Ada biaya Rp 6.500 (ditanggung player). Lumayan cepat.
     - BANK BIASA: Tergantung bank, ada limit per hari/bulan.
   → Clarify: "Jika belum Premium, upgrade dulu baru bisa WD E-wallet. Untuk bank digital, fee sudah jelas Rp 6.500"
   → Contoh: "WD 50.000 via bank digital → masuk rekening 43.500 (50.000 - 6.500)"

3. [INTENT: Withdraw Problem / Tidak Bisa Withdraw]
   User: "Ga bisa withdraw", "Button withdraw hilang", "Withdraw error", "Apa masalah kok ga bisa WD?"
   → Troubleshooting checklist (jawab satu-satu):
     ✓ "Turnover Anda sudah selesai?" → Jika belum, selesaikan TO dulu
     ✓ "Pakai E-wallet?" → Harus Premium status. Cek di profil, upgrade jika belum
     ✓ "Nomor rekening sudah benar?" → Pastikan rekening terdaftar + nominal kelipatan Rp 1000
     ✓ "Sudah ada withdraw pending?" → Tunggu selesai dulu (jangan multiple WD simultan)
     ✓ "Bank limit sudah capai?" → Lihat di bawah rule limit bank
   → Jika masih error setelah cek semua → "Silakan hubungi Human Support dengan screenshot error-nya ya Kak"

4. [INTENT: Withdraw Pending / Terlalu Lama]
   User: "Withdraw saya mana?", "Kok pending?", "Berapa lama proses withdraw?", "Sudah transfer tapi belum masuk rekening"
   → Jawab: "Biasanya WD masuk dalam hitungan menit sampai jam. Tapi bisa lebih lama tergantung bank. Cek status di riwayat transaksi dulu"
   → If pending 24+ jam: "Jika sudah lebih dari 24 jam belum masuk, hubungi Human Support dengan bukti screenshot. Mungkin ada kendala di bank"

5. [INTENT: Bank Limit Sudah Habis / "Jatah WD Habis"]
   User: "Bank limit habis", "Quota WD habis", "Kenapa ga bisa WD lagi?", "Kapan bisa WD lagi?"
   → Jawab: "Bank digital punya limit per hari/bulan yang ditentukan bank. Jika sudah capai limit → WD kembali bisa dilakukan tanggal 1 bulan depan"
   → Clarify: "Ini bukan kebijakan kami, tapi kebijakan dari bank. Untuk WD lebih banyak, bisa coba bank lain atau E-wallet (jika sudah Premium)"
   → Contoh: "Hari ini limit bank Anda sudah tercapai → Coba WD lagi besok atau tanggal 1 bulan depan limit reset"

6. [INTENT: Charge Withdraw Multiple Times]
   User: "Kenapa kena charge ganda?", "Biaya withdraw berapa kalau banyak?", "Ada biaya tambahan?"
   → Jawab: "Biaya normal seperti di atas. TAPI ada aturan special: Jika WD lebih dari 5 kali dalam sehari → Kena charge tambahan 2,5%"
   → Contoh: "WD kali ke-6 dalam sehari: Total WD Rp 100.000 → Charge 2,5% = Rp 2.500 → Masuk Rp 97.500"
   → Tip: "Gather WD beberapa hari, jangan WD 6+ kali dalam 1 hari biar tidak kena charge tambahan"

═══════════════════════════════════════════════════

IMPORTANT CLARIFICATIONS:

E-WALLET WITHDRAWAL:
✓ E-wallet WAJIB berstatus PREMIUM terlebih dahulu
✓ Tidak ada biaya tambahan (free)
✓ Proses cepat (biasanya menit)
✗ Jika belum Premium → tidak bisa WD, harus upgrade dulu

BANK DIGITAL WITHDRAWAL:
✓ Bank: Jago, SeaBank, blu BCA, Neo Bank, Jenius
✓ Biaya: Rp 6.500 (ditanggung player, bukan kami)
✓ Proses: Cepat (hitungan jam)
✗ Ada limit per hari/bulan (dari bank, bukan kami)

BANK WITHDRAWAL (Non-Digital):
✓ Bisa semua bank (BCA, Mandiri, BNI, dll)
✓ Biaya: Sesuai kebijakan bank
✓ Proses: Tergantung bank (biasanya 1-3 hari)
✗ Ada limit per hari/bulan

TURNOVER (TO):
✓ MUST diselesaikan sebelum WD
✓ TO adalah target betting yang harus dicapai
✓ Info TO ada di profil/dashboard
✗ Jika TO belum selesai → WD BLOCKED (system akan reject)

BANK LIMIT RESET:
✓ Limit bank reset tanggal 1 setiap bulan
✓ Bukan reset real-time (tunggu berganti tanggal)
✓ Jika ingin WD lebih, try bank lain atau E-wallet Premium

═══════════════════════════════════════════════════

TONE:
- Helpful, clarifying, not defensive
- Acknowledge frustration if WD issues ("Kesel ya kalo pending?")
- Pro-active suggest solutions (troubleshooting checklist)
- Direct ke Human Support jika di luar kemampuan'],
        ]);

        $this->seed($bonusId, [
            ['title' => 'Bonus', 'source' => 'manual', 'content' => '=== DAFTAR BONUS RESMI PLATFORM ===

INSTRUKSI AI:
- HANYA infokan bonus dari daftar di bawah.
- Jika member bertanya bonus yang tidak ada di sini → jawab "belum tersedia" atau arahkan ke Human Support.
- Gunakan info parameter (rate, min, multiplier, dll) dari daftar untuk detail akurat.

─────────────────────────────────────

A. BONUS MISI KLAIM (Manual klaim di Menu Reward sebelum bermain)

1. Bonus Ajak Teman / Referral [{ev_ref}]
   - Bagikan link referral dari menu Profil/Akun
   - Bonus masuk otomatis ketika teman deposit
   - Syarat: Player harus 1x deposit sukses terlebih dahulu sebelum teman bisa dapat bonus
   - Multiplier={ref_mul} | Min Deposit Per Teman={ref_min}-{ref_to} | Bonus per Teman={ref_bet} | Bonus jika >ref_to={ref_gt}

2. Bonus Deposit Beruntun / Daily Streak [{ev_brt}]
   - Deposit setiap hari berturut-turut (balance harus <1000 untuk dihitung beruntun)
   - Bonus = nominal dari deposit tertinggi dalam streak tersebut
   - Multiplier={brt_mul} | Min Deposit={brt_min} | Max Bonus={brt_max} | Jumlah Hari={brt_cnt}

3. Bonus Freespin [{ev_frp}]
   - Diberikan otomatis setelah deposit harian
   - Multiplier={fs_mul} | Min Deposit={fs_min}

4. Bonus Deposit Bank [{ev_bd}]
   - First Deposit Bank: Min={bfd_b_min} | Rate Bonus={bfd_b_rate}% | Multiplier={bfd_b_mul}
   - Daily Deposit Bank: Min={bdd_b_min} | Rate Bonus={bdd_b_rate}% | Multiplier={bdd_b_mul}
   - Catatan: Member tidak bisa dapat kedua bonus ini di hari yang sama

5. Bonus APK [{ev_ap}]
   - Hanya bisa diklaim jika download dan bermain via APK
   - Multiplier={bap_mul} | Jumlah Bonus={bap_bon} | Jumlah Deposit Minimal={bap_cou}

─────────────────────────────────────

B. Bonus Cashback (Masuk otomatis setiap hari {cb_day} - tidak perlu klaim)
   - Type Perhitungan={cb_type}
   - By Total: Min Turnover={cb_tot_min} | Rate Cashback={cb_tot_rate}%
   - By Game:
     • Slot: Min={cb_slot_min} | Rate={cb_slot_rate}%
     • Togel: Min={cb_tgl_min} | Rate={cb_tgl_rate}%
     • Live Casino: Min={cb_lc_min} | Rate={cb_lc_rate}%
     • Sabung Ayam: Min={cb_sa_min} | Rate={cb_sa_rate}%
     • Sports: Min={cb_spt_min} | Rate={cb_spt_rate}%
     • (dan lainnya sesuai game)

─────────────────────────────────────

C. Bonus Promo (Khusus - verifikasi lewat Human Support)
   - Bonus Buy Spin
   - Bonus Freespin (promosi khusus)
   - Bonus Winstreak Sabung Ayam
   → Untuk bonus promo ini → [Escalate] ke Human Support

─────────────────────────────────────

CATATAN PENTING:
✓ HANYA ada 5 bonus misi klaim + Cashback + Promo
✓ Jangan sebutkan bonus lain (tidak tersedia)
✓ Member HARUS klaim bonus di Menu Reward SEBELUM bermain (kecuali Cashback yang otomatis)
✓ Jika member tanya bonus yang tidak ada di sini → jawab: "Belum tersedia" atau "Silakan hubungi Human Support"'],
        ]);

        $this->seed($gameId, [
            ['title' => 'Pools', 'source' => 'manual', 'content' => '1 - HKP - Hongkong Lotto: Close 22:30 | Result 23:00 | Open 23:05 (Sun-Sat)
2 - SGP - Singapore Pools: Close 17:15 | Result 17:45 | Open 17:50 (Sun,Mon,Wed,Thu,Sat)
3 - SDY - Sydney Lotto: Close 13:25 | Result 13:55 | Open 14:00 (Sun-Sat)
4 - SMR - Samosir Pools: Close 19:30 | Result 20:00 | Open 20:05 (Sun-Sat)
5 - HKS - HK Siang: Close 10:30 | Result 11:00 | Open 11:05 (Sun-Sat)
6 - TMC - Toto Macau (6 sesi/hari Sun-Sat):
   Sesi 1: Close 23:45|Result 00:00|Open 00:05 | Sesi 2: Close 12:45|Result 13:00|Open 13:05
   Sesi 3: Close 15:45|Result 16:00|Open 16:05 | Sesi 4: Close 18:45|Result 19:00|Open 19:05
   Sesi 5: Close 21:45|Result 22:00|Open 22:05 | Sesi 6: Close 22:45|Result 23:00|Open 23:05
7 - CHP - China Pools: Close 15:15 | Result 15:30 | Open 15:35 (Sun-Sat)
8 - MGC - Cambodia: Close 19:30 | Result 19:50 | Open 19:55 (Sun-Sat)
9 - OG1 - Oregon 1: Close 02:45 | Result 03:00 | Open 03:05 (Sun-Sat)
10 - OG2 - Oregon 2: Close 05:45 | Result 06:00 | Open 06:05 (Sun-Sat)
11 - OG3 - Oregon 3: Close 08:45 | Result 09:00 | Open 09:05 (Sun-Sat)
12 - OG4 - Oregon 4: Close 11:45 | Result 12:00 | Open 12:05 (Sun-Sat)
13 - BE - Bullseye: Close 12:50 | Result 13:10 | Open 13:15 (Sun-Sat)
14 - SW - Swiss: Close 15:30 | Result 16:00 | Open 16:05 (Sun-Sat)
15 - MC - Macau: Close 19:30 | Result 20:00 | Open 20:05 (Sun-Sat)
16 - CAI - Cairo: Close 12:00 | Result 12:30 | Open 12:35 (Sun-Sat)
17 - TW - Taiwan: Close 23:20 | Result 23:50 | Open 23:55 (Sun-Sat)
18 - QTR - Qatar: Close 20:30 | Result 21:00 | Open 21:05 (Sun-Sat)
19 - MLY - Malaysia: Close 18:30 | Result 19:00 | Open 19:05 (Sun-Sat)'],
            ['title' => 'Sports', 'source' => 'manual', 'content' => 'Mix Parlay: menggabungkan beberapa pertandingan, semua tebakan harus benar.

HASIL DALAM TIKET PARLAY:
- Kalah (Lose): 1 pertandingan kalah = seluruh tiket gugur.
- Seri (Draw): Odds diubah ke 1, tiket tetap berjalan.
- Void/Ditunda (>24 jam): Odds menjadi 1, tiket tetap berjalan.
- Menang Setengah: odds = (Odds awal - 1) / 2 + 1.

HITUNG TOTAL ODDS: Kalikan semua odds. Contoh: 1.80 x 2.00 x 1.50 = 5.40
PAYOUT: Total Odds x Modal. Contoh: 5.40 x Rp100.000 = Rp540.000

JENIS TARUHAN:
1. HANDICAP (HDP): Tim kuat beri voor ke tim lemah.
2. OVER/UNDER (O/U): Total gol di atas/bawah angka tertentu.
3. 1X2: 1=Home menang, X=Draw, 2=Away menang.
4. ODD/EVEN (O/E): Total gol akhir ganjil atau genap.'],
            ['title' => 'Togel Info', 'source' => 'manual', 'content' => 'DAFTAR ISTILAH DAN PARAMETER TOGEL

4D: Win 3000x | Kei 0% | Min 500 | Discount 66%
3D Depan (3DD): Win 400x | Kei 0% | Min 500 | Discount 59%
3D Belakang (3DB): Win 400x | Kei 0% | Min 500 | Discount 59%
2D Depan (2DD): Win 70x | Kei 0% | Min 500 | Discount 29%
2D Tengah (2DT): Win 70x | Kei 0% | Min 500 | Discount 29%
2D Belakang (2DB): Win 70x | Kei 0% | Min 500 | Discount 29%
Colok Bebas (CB): Win 1x=1.5x, 2x=3x, 3x=4.5x, 4x=6x | Kei 0% | Min 5000 | Discount 6%
Colok Bebas 2D (CB2): Win 1x=6.5x, 2x=10x, 3x=18x | Kei 0% | Min 5000 | Discount 8%
Colok Naga (CB3): Win 1x=20x, 2x=29x | Kei 0% | Min 5000 | Discount 10%
Colok Jitu (CJ): Win 8x | Kei 0% | Min 5000 | Discount 6%
Tepi Tengah (TT): Win 1x | Kei -3% | Min 5000 | Discount 0%
Dasar (DS): Win 1x | Kei Ganjil -25%/Genap 10%/Besar -25%/Kecil 10% | Min 5000 | Discount 0%
50-50 (50): Win 1x | Kei -3% | Min 5000 | Discount 0%
Shio (SH): Win 9x | Kei 0% | Min 5000 | Discount 8%
Silang Homo (SHM): Win 1x | Kei -2.5% | Min 5000 | Discount 0%
Kembang Kempis (KK): Win 1x | Kei Kembang/Kempis -3%, Kembar 50% | Min 5000 | Discount 0%
Kombinasi (KM): Win 2.3x | Kei 0% | Min 5000 | Discount 8%

Catatan :
Jika player bertanya tentang history atau angka togel yang dipasang, arahkan player untuk:
Buka Game Togel di kategori Togel Dindong
Pilih tab "Invoice" untuk melihat riwayat taruhan'],
            ['title' => 'Link & Pola', 'source' => 'manual', 'content' => '=== PANDUAN LINK DAN POLA ===

DEFINISI:
- LINK: URL resmi RTP (Return To Player) dan APK download
- POLA: Strategi/pattern betting yang populer (misal: pola slot gacor, pola betting conservative, pola win-chase, dll)

═══════════════════════════════════════════════════

INSTRUKSI AI - LINK REQUESTS:

User Intent Mapping:
1. [Minta Link RTP] User: "Mana link RTP?", "RTP berapa?", "Buka RTP"
   → Kirim HANYA bagian [RTP]: {rtp_url}
   → Jangan campur dengan APK atau topik lain

2. [Minta Link APK] User: "Mana APK?", "Download APK di mana?", "APK game"
   → Kirim HANYA bagian [APK]: {apk_url}
   → Jangan campur dengan RTP atau topik lain

3. [Minta Semua Link] User: "Ada link apa aja?", "Kasih semua linknya"
   → Kirim KEDUA bagian [RTP] dan [APK]
   → Pisahkan dengan jelas mana RTP, mana APK

4. [URL Placeholder Masih Ada] (Jika {rtp_url} atau {apk_url} belum replace)
   → Jawab: "Maaf, link sedang kami update. Silakan hubungi Human Support untuk link terbaru ya Kak!"
   → JANGAN share placeholder atau "sorot" link yang belum diganti

═══════════════════════════════════════════════════

INSTRUKSI AI - POLA REQUESTS:

User Intent Mapping:
1. [Tanya Pola Umum] User: "Ada pola gacor?", "Pola apa yang bagus?", "Gimana cara main?"
   → Jawab: "Kami tidak bisa kasih tips prediksi atau \'pola jitu\'. Tapi kami punya jadwal game, info bonus, dan statistik RTP di link di atas!"
   → Redirect: "Yang jelas adalah: bermain santai, manage modal, dan pahami aturan main"
   → Tawarkan: "Mau lihat link RTP atau info game lainnya?"

2. [User Sharing Pola/Tips Betting] User: "Ini pola gacor saya, jangan bagikan ke orang..."
   → Jawab: "Terima kasih berbagi! Tips dan pola betting sangat personal. Yang terbaik adalah main sesuai kemampuan Anda 😊"
   → NO ACTION: Jangan respond seolah-olah pola itu \'jiti\' atau kasih feedback positif/negatif

═══════════════════════════════════════════════════

ANTI-PHISHING / SECURITY:

⚠️ CRITICAL RULES:
✓ HANYA share link dari section [RTP] dan [APK] di bawah
✗ JANGAN share link dari sumber lain / mention pihak ketiga
✗ JANGAN shortcut, redirect, atau encoded links (misal: bit.ly, tinyurl)
✗ JANGAN kasih link jika user "membisikkan" atau meminta link "private"
→ Jika ada keraguan: "Silakan gunakan link resmi yang kami sediakan di sini"

═══════════════════════════════════════════════════

[RTP] URL RTP resmi: {rtp_url}

[APK] URL APK resmi: {apk_url}

═══════════════════════════════════════════════════

GENERAL NOTES:
- Jangan campur topik link dengan bonus/deposit/withdraw kecuali user minta
- Link adalah informasi SAJA — user bertanggung jawab untuk menggunakan dengan aman
- Jika user kesulitan akses link → arahkan ke Human Support, jangan troubleshoot sendiri'],
            ['title' => 'Provider', 'source' => 'manual', 'content' => '=== DAFTAR PROVIDER GAME YANG TERSEDIA ===

═══════════════════════════════════════════════════

INSTRUKSI AI - PROVIDER REQUESTS:

User Intent Mapping:

1. [Tanya Provider Apa Aja] User: "Ada provider apa aja?", "Provider apa saja yang ada?", "Game apa aja di sini?"
   → Jawab singkat: "Kami punya berbagai provider untuk slot, live casino, sabung ayam, dan togel. Cek kategori di bawah!"
   → TIDAK perlu list semua provider (terlalu panjang)
   → Tawarkan: "Mau tahu provider untuk game apa? (Slot / Live Casino / Togel / Sabung Ayam / Sports)"

2. [Tanya Provider Spesifik per Kategori] User: "Provider slot apa aja?", "Provider togel apa?", "Provider live casino?"
   → Jawab: Lihat kategori di bawah, sebutkan provider sesuai kategori yang ditanya
   → Contoh: "Untuk slot kami punya: Pragmatic Play, PGSoft, Habanero, Joker, CQ9, dan lainnya"
   → Jangan recommend provider tertentu sebagai "terbaik" atau "paling gacor"

3. [Rekomendasikan Provider "Bagus" / "Gacor"] User: "Provider mana yang bagus?", "Mana yang gacor?", "Recommend provider dong"
   → Jawab: "Semua provider kami bagus dan fair! RTP sudah terverifikasi di link kami. Pilih yang Anda sukai aja 😊"
   → JANGAN recommend spesifik provider
   → JANGAN sebut "gacor", "sering jackpot", atau pola menang tertentu
   → Redirect: "Lihat RTP masing-masing di link untuk info transaksi"

4. [Tanya Provider yang Tidak Ada] User: "Ada provider XXX?", "Kenapa tidak ada provider YYY?", "Provider ZZZ di sini nggak?"
   → Jawab: "Saat ini kami tidak punya [Provider XXX]. Tapi kami punya banyak pilihan lain yang bagus! Mau coba provider lain?"
   → JANGAN konfirmasi atau breakdown kenapa provider tertentu tidak ada
   → Redirect: Tawarkan kategori/game yang berbeda

═══════════════════════════════════════════════════

ANTI-RECOMMENDATION RULES (CRITICAL):

⚠️ DO NOT:
✗ Recommend spesifik provider sebagai "terbaik", "paling gacor", atau "paling sering jackpot"
✗ Buat ranking provider (provider A > provider B)
✗ Sebut provider punya "sistem curang" atau "mudah menang"
✗ Buat janji ROI atau win guarantee untuk provider tertentu

✓ DO:
✓ Sebutkan semua provider tersedia tanpa bias
✓ Redirect user ke RTP link untuk info transaksi
✓ Encourage user memilih sesuai preferensi (tema, gameplay, dll)

═══════════════════════════════════════════════════

PROVIDER CATEGORIES:

[SLOT]
- Pragmatic Play
- PGSoft
- Habanero
- Joker
- CQ9
- AWC
- KA Gaming
- Spade Gaming
- ION
- FastSpin
- Elysium
- Evo RedTiger
- Evo No Limit City
- Hacksaw
- WonWon
- Fat Panda
- Cosmo
- Saba
- SAGaming
- CMD
- SimplePlay
- Micro Gaming
- JiLi

[LIVE CASINO]
- Evolution
- Playtech
- SBO
- TF
- Spribe

[SABUNG AYAM]
- Ae Sexy

[TOGEL]
- DDT (Dindong)
- Togel (Togel)

[SPORTS]
- SBO
- Saba
- SAGaming

═══════════════════════════════════════════════════

GENERAL NOTES:
- Semua provider fair dan sudah verified dengan RTP transparan
- User bisa main provider apa saja sesuai selera (tema, gameplay)
- RTP dan statistik transaksi bisa lihat di link resmi kami
- Jika ada pertanyaan teknis provider → arahkan ke Human Support'],
        ]);

        // Assign all KB entries to triage
        $this->assignAllToTriage($triageId);
    }

    private function seed(?int $agentId, array $entries): void
    {
        if ($agentId === null) {
            return;
        }

        $agent = \App\Models\ChatAgent::find($agentId);
        if ($agent === null) {
            return;
        }

        foreach ($entries as $entry) {
            $kb = KnowledgeBase::query()->create(array_merge([
                'chat_agent_id' => null,
                'file_name'     => null,
                'data_model_id' => null,
                'query_sql'     => null,
                'is_active'     => true,
            ], $entry));
            $agent->knowledgeBases()->syncWithoutDetaching([$kb->id]);
        }
    }

    private function assignAllToTriage(?int $triageId): void
    {
        if ($triageId === null) {
            return;
        }
        $triageAgent = \App\Models\ChatAgent::find($triageId);
        if ($triageAgent === null) {
            return;
        }
        $allKbIds = KnowledgeBase::query()->pluck('id')->toArray();
        $triageAgent->knowledgeBases()->syncWithoutDetaching($allKbIds);
    }
}
