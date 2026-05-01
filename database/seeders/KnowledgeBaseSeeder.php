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
      $defaultAgent = ChatAgent::getDefault() ?? ChatAgent::query()->first();
      if ($defaultAgent === null) {
         return;
      }

      $providersDataModelId = DataModel::query()
          ->where('slug', 'providers')
          ->value('id');

        $entries = [
            [
                'title' => 'Pools',
                'content' => '1 - HKP - Hongkong Lotto
   Sunday - Saturday : Close 22:30 | Result 23:00 | Open 23:05

2 - SGP - Singapore Pools
   Sunday, Monday, Wednesday, Thursday, Saturday : Close 17:15 | Result 17:45 | Open 17:50

3 - SDY - Sydney Lotto
   Sunday - Saturday : Close 13:25 | Result 13:55 | Open 14:00

4 - SMR - Samosir Pools
   Sunday - Saturday : Close 19:30 | Result 20:00 | Open 20:05

5 - HKS - HK Siang
   Sunday - Saturday : Close 10:30 | Result 11:00 | Open 11:05

6 - TMC - Toto Macau
   Sunday - Saturday (6 sesi per hari):
   Sesi 1 : Close 23:45 | Result 00:00 | Open 00:05
   Sesi 2 : Close 12:45 | Result 13:00 | Open 13:05
   Sesi 3 : Close 15:45 | Result 16:00 | Open 16:05
   Sesi 4 : Close 18:45 | Result 19:00 | Open 19:05
   Sesi 5 : Close 21:45 | Result 22:00 | Open 22:05
   Sesi 6 : Close 22:45 | Result 23:00 | Open 23:05

7 - CHP - China Pools
   Sunday - Saturday : Close 15:15 | Result 15:30 | Open 15:35

8 - MGC - Cambodia
   Sunday - Saturday : Close 19:30 | Result 19:50 | Open 19:55

9 - OG1 - Oregon 1
   Sunday - Saturday : Close 02:45 | Result 03:00 | Open 03:05

10 - OG2 - Oregon 2
   Sunday - Saturday : Close 05:45 | Result 06:00 | Open 06:05

11 - OG3 - Oregon 3
   Sunday - Saturday : Close 08:45 | Result 09:00 | Open 09:05

12 - OG4 - Oregon 4
   Sunday - Saturday : Close 11:45 | Result 12:00 | Open 12:05

13 - BE - Bullseye
   Sunday - Saturday : Close 12:50 | Result 13:10 | Open 13:15

14 - SW - Swiss
   Sunday - Saturday : Close 15:30 | Result 16:00 | Open 16:05

15 - MC - Macau
   Sunday - Saturday : Close 19:30 | Result 20:00 | Open 20:05

16 - CAI - Cairo
   Sunday - Saturday : Close 12:00 | Result 12:30 | Open 12:35

17 - TW - Taiwan
   Sunday - Saturday : Close 23:20 | Result 23:50 | Open 23:55

18 - QTR - Qatar
   Sunday - Saturday : Close 20:30 | Result 21:00 | Open 21:05

19 - MLY - Malaysia
   Sunday - Saturday : Close 18:30 | Result 19:00 | Open 19:05',
                'source' => 'manual',
                'file_name' => null,
                'is_active' => true,
            ],
            [
                'title' => 'Bonus',
                'content' => 'A. BONUS MISI KLAIM , Harus Di Claim di Menu Reward.
1. Bonus Ajak Teman (Referral Bonus) [{ev_ref}]
Program Referral Bonus adalah program yang memungkinkan pengguna mendapatkan bonus dengan mengajak teman untuk bergabung dan bermain. 

Cara mengikuti program ini:
- Bagikan link referral yang tersedia di menu Profil/Akun.
- Ajak teman untuk mendaftar melalui link tersebut.
- Bonus akan diberikan secara otomatis setelah teman yang diundang melakukan deposit.
- [Penting] Player yang mendapatkan Bonus Harus melakukan 1 kali deposit sukses sebelum Teman yang diajak melakukan Deposit.

[Setting]
- Multiplier = {ref_mul}
- Minimal Deposit = {ref_min}-{ref_to}
- Teman Deposit {ref_min}-{ref_to} Dapat Bonus {ref_bet}
- Teman Deposit > {ref_to} Dapat Bonus {ref_gt}

2. Bonus Deposit Beruntun (Daily Streak Bonus) [{ev_brt}]
Bonus Deposit Beruntun adalah bonus spesial yang bisa didapatkan dengan melakukan deposit setiap hari secara berturut-turut. Pastikan Balance dibawah 1000 untuk dihitung sebagai deposit beruntun. Jumlah Bonus adalah Nominal Deposit tertinggi dari jumlah beruntun.

[Setting]
- Multiplier = {brt_mul}
- Minimal Deposit = {brt_min}
- Max Bonus = {brt_max}
- Jumlah Beruntun = {brt_cnt}
- Termasuk Non Bank = {brt_nonbank}

3. Bonus Freespin [{ev_frp}]
Bonus Freespin adalah bonus yang diberikan setelah pengguna melakukan deposit harian.
Bonus ini biasanya berupa putaran gratis (free spin) yang dapat digunakan pada permainan tertentu sesuai ketentuan yang berlaku.

[Setting]
- Multiplier = {fs_mul}
- Minimal Deposit = {fs_min}
- Termasuk Non Bank = {fs_nonbank}

4. Bonus Deposit [{ev_bd}]
- Bonus First Deposit Bank
  Bonus yang diberikan pada saat pengguna melakukan deposit pertama kali.

  [Setting]
  - Minimal Deposit = {bfd_b_min}
  - Rate = {bfd_b_rate}%
  - Multiplier = {bfd_b_mul}

- Bonus Daily Deposit Bank
  Bonus yang diberikan untuk setiap deposit harian setelah deposit pertama.

  [Setting]
  - Minimal Deposit = {bdd_b_min}
  - Rate = {bdd_b_rate}%
  - Multiplier = {bdd_b_mul}

Catatan:
Jika pengguna telah mendapatkan Bonus First Deposit Bank, maka tidak dapat lagi mendapatkan Bonus Daily Deposit Bank pada hari yang sama.


5. Bonus APK [{ev_ap}]
Bonus APK adalah bonus yang hanya dapat diklaim melalui aplikasi (APK).

[Setting]
- Multiplier = {bap_mul}
- Jumlah Bonus = {bap_bon}
- Jumlah Deposit = {bap_cou}

6. Welcome Bonus New Member 100% [AKTIF]

[Setting]
- Multiplier = 1x
- First Deposit
- Minimal Deposit = 10000
- Category To = Slot

B. Bonus Cashback
Bonus Cashback adalah bonus yang diberikan kepada member setiap hari {cb_day}.
Bonus Cashback tidak di Claim di menu Reward, tetapi masuk otomatis.

[Setting]
- Type = {cb_type}
By Total: 
(mininum = {cb_tot_min}) | (rate = {cb_tot_rate}%)

By Game:
Dindong = (mininum = {cb_dd_min}) - (rate = {cb_dd_rate}%)
Togel = (minimum = {cb_tgl_min}) - (rate = {cb_tgl_rate}%)
Tangkas = (minimum = {cb_tgk_min}) - (rate = {cb_tgk_rate}%)
Slot = (minimum = {cb_slot_min}) - (rate = {cb_slot_rate}%)
Live Casino = (minimum = {cb_lc_min}) - (rate = {cb_lc_rate}%)
Sabung Ayam = (minimum = {cb_sa_min}) - (rate = {cb_sa_rate}%)
Arcade = (minimum = {cb_arc_min}) - (rate = {cb_arc_rate}%)
Table Game = (minimum = {cb_tbl_min}) - (rate = {cb_tbl_rate}%)
Sports = (minimum = {cb_spt_min}) - (rate = {cb_spt_rate}%)
E-Sports = (minimum = {cb_esp_min}) - (rate = {cb_esp_rate}%)

C. Bonus Promo
Bonus yang harus di verifikasi oleh Human Support.
- Bonus Buy Spin , Bonus Freespin
- Bonus winstreak sabung ayam
[Escalate] ke Human Support

Catatan:
- Tidak ada bonus lain selain bonus diatas.',
                'source' => 'manual',
                'file_name' => null,
                'is_active' => true,
            ],
            [
                'title' => 'Deposit',
                'content' => '[Informasi]
- Minimal Deposit Rp {dep_min}
- Maximal Deposit Rp Tak terbatas
- Deposit Menggunakan Pulsa Wajib Beserta SN Atau Nomor HP pengirim di Berita Deposit
- Multiplier Deposit Bank = {dep_mul_bank}
- Multiplier Deposit Non Bank = {dep_mul_nonbank}

[BANK]
BCA, BII / Maybank, BNI, BRI, BSI, BTN, CIMB Niaga, Niaga Syariah, Dana, Danamon, Mandiri, OCBC NISP, Bank Neo, Sea Bank, Jago, Permata, BTPN/Jenius, Bank MAS, Mandiri Syariah, BCA Syariah, QRIS
(E-Wallet) = Ovo, Shopee , Gopay , LINK AJA , Dana

[NON BANK]
(Pulsa) = XL, Telkomsel

Cara Deposit Melalui Pulsa:
Buka website kami, lalu masuk ke menu Form Deposit.
Pilih metode deposit menggunakan pulsa, misalnya XL atau Telkomsel.
Kirim pulsa ke nomor yang ditentukan.
Setelah berhasil, isi form deposit sesuai dengan nominal pulsa yang dikirim.
Jangan lupa di kolom Keterangan, cantumkan SN atau nomor pengirim (jika melakukan transfer pulsa).
Ajukan form deposit untuk menyelesaikan proses.

Catatan:
Deposit pulsa bisa dilakukan dari mana saja: konter pulsa, transfer pulsa melalui 858, top-up mobile banking, atau metode lain yang tersedia.

Deposit Bank:
Deposit Berbeda bank akan dikenakan biaya sebesar 6.500 atau 2.500 (BI-Fast).
Saat melakukan transfer antar bank, pastikan nominal yang dikirim sesuai dengan nominal permintaan, karena biaya akan ditanggung oleh pemain/user.
PENTING: Nominal di form deposit harus sama persis dengan nominal permintaan deposit (bukan ditambah biaya admin).
Contoh 1: jika permintaan deposit 20.000, isi form deposit 20.000.
Contoh 2: jika permintaan deposit 12.000, isi form deposit 12.000.
Biaya antar bank 6.500 atau 2.500 (BI-Fast) adalah biaya tambahan dari bank dan ditanggung user di luar nominal deposit.
Wajib menggunakan rekening pribadi dengan nama yang sama persis seperti yang terdaftar di profil akun.

Deposit QRIS:
Deposit QRIS tidak wajib mengunakan rekening yang asli dengan nama yang sama seperti terdaftar di profil akun.
Bisa jadi alternatif jika terjadi masalah perbedaan nama rekening saat deposit bank.

Deposit Beberapa Kali Transfer (Multiple Transfer):
Jika Kakak sudah melakukan beberapa kali transfer untuk 1 tujuan deposit, cukup isi 1x form deposit saja 😊
Masukkan total keseluruhan nominal dari semua transfer yang sudah dilakukan.
Contoh: Transfer 100.000 + 200.000 + 300.000 = isi form 600.000
Jangan submit form lebih dari 1x — cukup 1 form dengan total semua transfer.',
                'source' => 'manual',
                'file_name' => null,
                'is_active' => true,
            ],
            [
                'title' => 'Sports',
                'content' => 'Mix Parlay adalah taruhan yang menggabungkan beberapa pertandingan dalam satu tiket, dan semua tebakan harus benar agar menang.

HASIL PERTANDINGAN DALAM TIKET PARLAY:
- Kalah (Lose): Satu pertandingan saja kalah → seluruh tiket gugur/kalah total.
- Seri (Draw): Odds pertandingan tersebut diubah menjadi 1, tidak mempengaruhi tiket, sisa pertandingan tetap berjalan.
- Void/Ditunda: Pertandingan dibatalkan atau ditunda >24 jam → dianggap Void, odds menjadi 1, tiket tetap berjalan.
- Menang Setengah (Won Half): Tiket tetap hidup, tetapi odds dihitung ulang dengan rumus: (Odds awal - 1) ÷ 2 + 1.

CARA HITUNG TOTAL ODDS:
Total odds = kalikan semua odds pertandingan yang dipilih.
Contoh: 1.80 × 2.00 × 1.50 = 5.40

CARA HITUNG PAYOUT (KEMENANGAN):
Payout = Total Odds × Modal taruhan.
Keuntungan bersih = Payout - Modal.
Contoh: 5.40 × Rp100.000 = Rp540.000 (payout), keuntungan bersih = Rp440.000.


Berikut jenis taruhan olahraga yang tersedia:

1. HANDICAP (HDP / Asian Handicap)
   Tim kuat memberi voor (nilai gol tambahan) ke tim lemah untuk menyeimbangkan peluang.
   Contoh: Tim A -1 berarti Tim A harus menang minimal 2 gol agar taruhan menang.

2. OVER/UNDER (O/U)
   Menebak apakah total gol kedua tim berada di atas (Over) atau di bawah (Under) angka yang ditentukan.
   Contoh: O/U 2.5 — Over jika total gol ≥ 3, Under jika total gol ≤ 2.

3. 1X2
   Format taruhan klasik tanpa handicap:
   - 1 = Home (tuan rumah menang)
   - X = Draw (seri)
   - 2 = Away (tim tamu menang)

4. ODD/EVEN (O/E)
   Menebak apakah total gol akhir pertandingan bernilai ganjil (Odd) atau genap (Even).',
                'source' => 'manual',
                'file_name' => null,
                'is_active' => true,
            ],
            [
                'title' => 'Withdraw',
                'content' => '[Informasi]
-Minimal Withdraw Rp {wd_min}
-Withdraw berkelipatan Rp 1000

Withdraw dengan E-WALLET:
Semua akun E-Wallet wajib berstatus Premium sebelum penarikan dana/Withdraw(WD).
Pastikan upgrade E-Wallet terlebih dahulu jika belum Premium.
E-Wallet Premium adalah Wajib sejak awal.

Withdraw dengan Bank Digital:
[ Bank Jago , SeaBank , blu by BCA Digital , Bank Neo Commerce (Neo Bank / Neo+) , Jenius (BTPN)]
Semua penarikan ke rekening Bank Digital, akan di kenakan 6.500 ditanggung Player.
Misalnya: WD 50.000 , maka akan masuk ke Rekening 43.500 yaitu dari 50.000 - 6.500(Biaya Admin).

BIAYA PENARIKAN (WD):
Penarikan dikenakan charge 2,5% jika jumlah WD melebihi 5 kali dalam sehari.',
                'source' => 'manual',
                'file_name' => null,
                'is_active' => true,
            ],
            [
                'title' => 'Register',
                'content' => 'PANDUAN AI – MEMBER BARU (INFORMASI SITUS & DAFTAR)

TUJUAN:
Memberikan informasi jelas dan mengarahkan user sampai daftar.

---

ATURAN:
- Jawab sesuai pertanyaan (jangan keluar konteks)
- Gunakan bahasa ramah & santai
- Jangan cek nomor tanpa diminta
- Jangan beri info sensitif (password, dll)
- Arahkan ke registrasi (soft selling)
- Jangan bahas umur kecuali ditanya

---

INTENT USER:

1. INFORMASI SITUS
→ Jelaskan:
- Permainan: slot, live casino, togel, dll
- Bonus & promo
- Deposit & withdraw cepat
- Support 24 jam
→ Tutup dengan ajakan daftar

2. SYARAT DAFTAR
→ Jelaskan data:
- Username & password
- Nomor HP aktif
- Rekening bank / E-wallet

→ Ketentuan:
- Tidak ada batasan umur
- Bisa pakai rekening orang lain (data harus sesuai)
- Deposit harus dari rekening terdaftar
- Jika beda rekening, gunakan QRIS
- Jika menggunakan E-Wallet, disarankan sudah **premium** (WD ke e-wallet wajib premium)

→ Tawarkan bantuan daftar

---

CONTOH:

[INFO]
"Halo kak 👋
Kami menyediakan slot, live casino, dan togel.

Keunggulan:
✅ Bonus member baru
✅ Promo harian
✅ WD & deposit cepat
✅ Support 24 jam

Mau saya bantu daftar kak? 😊"

---

[DAFTAR]
"Untuk daftar cukup siapkan:
- Username & password
- Nomor HP aktif
- Rekening bank / E-wallet

Catatan:
- Tidak ada batasan umur
- Bisa pakai rekening orang lain
- Deposit harus dari rekening terdaftar
- Bisa QRIS jika beda rekening
- E-wallet disarankan sudah premium (untuk proses WD)

Saya bisa bantu pandu daftarnya ya 👍"

---

JIKA KELUAR TOPIK:
"Sepertinya ada sedikit salah paham ya kak 🙏
Saya bantu jelaskan kembali info pendaftaran ya 😊',
                'source' => 'manual',
                'file_name' => null,
                'is_active' => true,
            ],
            [
                'title' => 'General',
                'content' => 'PARTNER SITE HANDLING

Daftar situs partner:
- CMBET
- BIGMSG
- GSC11
- IDXBIG

Aturan respon:

1. Jika user menanyakan atau menyebut salah satu nama situs di atas:
   - Jawab bahwa situs tersebut adalah "web partner"

2. Jika user menanyakan situs lain di luar daftar:
   - Jawab bahwa tidak ada relasi atau tidak dikenal

Catatan:
- Jawaban harus singkat dan jelas
- Jangan menambahkan informasi di luar konteks',
                'source' => 'manual',
                'file_name' => null,
                'is_active' => true,
            ],
            [
               'title' => 'Link & Pola',
               'content' => 'PANDUAN LINK & POLA (JAWAB CEPAT DAN SPESIFIK)

   ATURAN INTENT:
   - Jika user minta link RTP, kirim bagian RTP saja.
   - Jika user minta link APK, kirim bagian APK saja.
   - Jika user minta pola gacor, kirim bagian Pola saja.
   - Jika user minta semua, baru kirim RTP + APK + Pola berurutan.

   [RTP]
   URL RTP resmi:
   {rtp_url}

   [APK]
   URL APK resmi:
   {apk_url}

   [Game Gacor / Slot Gacor]
   {game_gacor}

   [POLA VIP / POLA GACOR]
   {pola_gacor}

   CATATAN RESPON AI:
   - Jangan campur dengan topik bonus/deposit jika user tidak minta.
   - Jika user hanya menulis "rtp", "apk", "game gacor" atau "pola gacor", jawab dengan sopan.',
                'source' => 'manual',
                'file_name' => null,
                'is_active' => true,
            ],
            [
               'title' => 'Provider',
               'content' => null,
               'source' => 'datamodel',
               'file_name' => null,
               'data_model_id' => $providersDataModelId,
               'query_sql' => 'SELECT * FROM providers WHERE active = 1',
               'is_active' => true,
            ],
        ];

        foreach ($entries as $entry) {
            KnowledgeBase::query()->updateOrCreate(
            [
               'chat_agent_id' => $defaultAgent->id,
               'title' => $entry['title'],
            ],
            [
               'chat_agent_id' => $defaultAgent->id,
               'content' => $entry['content'],
               'source' => $entry['source'],
               'file_name' => $entry['file_name'],
               'data_model_id' => $entry['data_model_id'] ?? null,
               'query_sql' => $entry['query_sql'] ?? null,
               'is_active' => $entry['is_active'],
            ]
            );
        }
    }
}
