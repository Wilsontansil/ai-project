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
            ['title' => 'General', 'source' => 'manual', 'content' => 'GENERAL WEBSITE KNOWLEDGE

Nama website utama: PGS
PGS adalah situs game online terpercaya di Indonesia dengan sistem keamanan canggih, promo eksklusif, deposit QRIS cepat, dan layanan 24 jam nonstop.
Domain utama: {main_domain}

Jika user bertanya tentang website, nama website, domain, link, atau akses situs:
- Selalu sebutkan bahwa website adalah PGS
- Wajib sertakan URL domain utama: {main_domain}

PARTNER SITE HANDLING:
Situs partner: CMBET, BIGMSG, GSC11, IDXBIG
Jika user menyebut salah satu: jawab bahwa situs tersebut adalah web partner.
Jika situs lain di luar daftar: tidak ada relasi.'],
        ]);

        $this->seed($akunId, [
            ['title' => 'Register', 'source' => 'manual', 'content' => 'PANDUAN AI MEMBER BARU

ATURAN: Jawab sesuai pertanyaan, bahasa ramah, jangan cek data tanpa diminta.

INTENT:
1. TANYA INFO SITUS -> slot, live casino, togel, sabung ayam, sports, bonus & promo harian, WD & deposit cepat, support 24 jam. Tutup dengan ajakan daftar.
2. TANYA SYARAT DAFTAR -> Username + Rekening bank/E-wallet. Tidak ada batasan umur. Boleh rekening orang lain (nama sesuai profil). E-wallet wajib Premium untuk WD.
3. LANGSUNG MINTA DAFTAR -> Minta: username + nomor rekening/E-wallet + nama pemilik.

CONTOH:
[INFO SITUS] "Halo kak! Kami punya slot, live casino, togel, sabung ayam! Bonus member baru, promo harian, WD & deposit cepat, support 24 jam. Mau saya bantu daftar? 😊"
[SYARAT DAFTAR] "Cukup siapkan username dan rekening bank/E-wallet (nama sesuai profil). Saya bantu pandu daftarnya ya kak!"
[LANGSUNG DAFTAR] "Siap kak! Boleh share: 1. Username yang diinginkan 2. Nomor rekening/E-wallet + nama pemilik"
JIKA KELUAR TOPIK: "Saya khusus bantu pendaftaran. Ada yang mau ditanyakan? 😊"'],
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
            ['title' => 'Withdraw', 'source' => 'manual', 'content' => '[Informasi]
- Minimal Withdraw Rp {wd_min}
- Withdraw berkelipatan Rp 1000

Withdraw dengan E-WALLET: Semua akun E-Wallet wajib berstatus Premium. Pastikan upgrade terlebih dahulu.

Withdraw dengan Bank Digital (Bank Jago, SeaBank, blu BCA, Neo Bank, Jenius):
Dikenakan biaya 6.500 ditanggung Player.
Contoh: WD 50.000 -> masuk rekening 43.500 (50.000 - 6.500).

Rule:
- Jika limit bank sudah capai, WD kembali di tanggal 1 bulan depan.
- Turnover (TO) harus diselesaikan dulu sebelum WD.

BIAYA PENARIKAN (WD): Charge 2,5% jika WD lebih dari 5 kali dalam sehari.'],
        ]);

        $this->seed($bonusId, [
            ['title' => 'Bonus', 'source' => 'manual', 'content' => 'A. BONUS MISI KLAIM (Claim di Menu Reward sebelum bermain)

1. Bonus Ajak Teman (Referral) [{ev_ref}]
- Bagikan link referral di menu Profil/Akun.
- Bonus otomatis setelah teman deposit.
- Player harus 1x deposit sukses sebelum teman deposit.
- Multiplier={ref_mul} | Min Deposit={ref_min}-{ref_to} | Bonus={ref_bet} | >ref_to={ref_gt}

2. Bonus Deposit Beruntun [{ev_brt}]
- Deposit setiap hari berturut-turut. Balance harus <1000 untuk dihitung.
- Bonus = nominal deposit tertinggi dari beruntun.
- Multiplier={brt_mul} | Min={brt_min} | Max={brt_max} | Jumlah={brt_cnt}

3. Bonus Freespin [{ev_frp}]
- Diberikan setelah deposit harian.
- Multiplier={fs_mul} | Min={fs_min}

4. Bonus Deposit [{ev_bd}]
- First Deposit Bank: Min={bfd_b_min} | Rate={bfd_b_rate}% | Mul={bfd_b_mul}
- Daily Deposit Bank: Min={bdd_b_min} | Rate={bdd_b_rate}% | Mul={bdd_b_mul}
(Tidak bisa dapat keduanya di hari yang sama)

5. Bonus APK [{ev_ap}]
- Hanya bisa diklaim via APK.
- Multiplier={bap_mul} | Jumlah Bonus={bap_bon} | Jumlah Deposit={bap_cou}

B. Bonus Cashback (masuk otomatis setiap hari {cb_day})
- Type={cb_type}
- By Total: min={cb_tot_min} | rate={cb_tot_rate}%
- By Game: Slot={cb_slot_min}/{cb_slot_rate}% | Togel={cb_tgl_min}/{cb_tgl_rate}% | Live Casino={cb_lc_min}/{cb_lc_rate}%
  Sabung Ayam={cb_sa_min}/{cb_sa_rate}% | Sports={cb_spt_min}/{cb_spt_rate}% | dll.

C. Bonus Promo (verifikasi Human Support)
- Bonus Buy Spin, Freespin, Winstreak sabung ayam -> [Escalate] ke Human Support

Catatan: Tidak ada bonus lain selain bonus di atas.'],
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
Kombinasi (KM): Win 2.3x | Kei 0% | Min 5000 | Discount 8%'],
            ['title' => 'Link & Pola', 'source' => 'manual', 'content' => 'PANDUAN LINK

ATURAN INTENT:
- Jika user minta link RTP, kirim bagian RTP saja.
- Jika user minta link APK, kirim bagian APK saja.
- Jika user minta semua, kirim RTP + APK.

[RTP] URL RTP resmi: {rtp_url}
[APK] URL APK resmi: {apk_url}

CATATAN: Jangan campur dengan topik bonus/deposit jika user tidak minta.'],
            ['title' => 'Provider', 'source' => 'datamodel', 'content' => null,
             'data_model_id' => $providersDataModelId,
             'query_sql' => 'SELECT * FROM providers WHERE active = 1'],
        ]);
    }

    private function seed(?int $agentId, array $entries): void
    {
        if ($agentId === null) {
            return;
        }

        foreach ($entries as $entry) {
            KnowledgeBase::query()->create(array_merge([
                'chat_agent_id' => $agentId,
                'file_name'     => null,
                'data_model_id' => null,
                'query_sql'     => null,
                'is_active'     => true,
            ], $entry));
        }
    }
}
