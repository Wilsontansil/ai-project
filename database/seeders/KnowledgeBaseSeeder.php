<?php

namespace Database\Seeders;

use App\Models\ChatAgent;
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
                'content' => 'A. BONUS KLAIM
1. Bonus Ajak Teman (Referral Bonus) [NONAKTIF]
Program Referral Bonus adalah program yang memungkinkan pengguna mendapatkan bonus dengan mengajak teman untuk bergabung dan bermain. Harus Di Claim di menu Reward.

Cara mengikuti program ini:
- Bagikan link referral yang tersedia di menu Profil/Akun.
- Ajak teman untuk mendaftar melalui link tersebut.
- Bonus akan diberikan secara otomatis setelah teman yang diundang melakukan deposit.
- [Penting] Player yang mendapatkan Bonus Harus melakukan 1 kali deposit sukses sebelum Teman yang diajak melakukan Deposit.

[Setting]
- Multiplier = 1x
- Minimal Deposit = 20000-30000
- Teman Deposit 20000-30000 Dapat Bonus 20000
- Teman Deposit > 30000 Dapat Bonus 30000


2. Bonus Deposit Beruntun (Daily Streak Bonus) [AKTIF]
Bonus Deposit Beruntun adalah bonus spesial yang bisa didapatkan dengan melakukan deposit setiap hari secara berturut-turut. Pastikan Balance dibawah 1000 untuk dihitung sebagai deposit beruntun.
Harus Di Claim di menu Reward.

[Setting]
- Multiplier = 1x
- Minimal Deposit = 50000
- Max Bonus = 50000
- Jumlah Beruntun = 8x
- Termasuk Non Bank = false


3. Bonus Freespin [AKTIF]
Bonus Freespin adalah bonus yang diberikan setelah pengguna melakukan deposit harian.
Harus Di Claim di menu Reward.
Bonus ini biasanya berupa putaran gratis (free spin) yang dapat digunakan pada permainan tertentu sesuai ketentuan yang berlaku.

[Setting]
- Multiplier = 1x
- Minimal Deposit = 2000
- Termasuk Non Bank = false


4. Bonus Deposit [NONAKTIF]
- Bonus First Deposit Bank
  Bonus yang diberikan pada saat pengguna melakukan deposit pertama kali. Harus Di Claim di menu Reward.

  [Setting]
  - Minimal Deposit = 2000
  - Multiplier = 1x

- Bonus Daily Deposit Bank
  Bonus yang diberikan untuk setiap deposit harian setelah deposit pertama. Harus Di Claim di menu Reward.

  [Setting]
  - Minimal Deposit = 2000
  - Multiplier = 1x

Catatan:
Jika pengguna telah mendapatkan Bonus First Deposit Bank, maka tidak dapat lagi mendapatkan Bonus Daily Deposit Bank pada hari yang sama.


5. Bonus APK [NONAKTIF]
Bonus APK adalah bonus yang hanya dapat diklaim melalui aplikasi (APK). Harus Di Claim di menu Reward.

[Setting]
- Multiplier = 1x

6. Welcome Bonus New Member 100% [AKTIF]

[Setting]
- Multiplier = 1x
- First Deposit
- Minimal Deposit = 10000
- Category To = Slot

B. Bonus Cashback
Bonus Cashback adalah bonus yang diberikan kepada member setiap hari Senin berdasarkan aktivitas deposit dan withdraw minggu lalu (Senin–Minggu).
Bonus Cashback tidak di Claim di menu Reward, tetapi masuk otomatis.

Syarat kelayakan:
- Hanya transaksi dengan status accept yang dihitung.
- Jika Total Deposit > Total Withdraw → member berhak menerima cashback dari selisihnya.
- Jika Total Withdraw > Total Deposit → member tidak dapat cashback (sudah profit).

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
- Minimal Deposit Rp 10.000
- Maximal Deposit Rp Tak terbatas
- Wajib deposit menggunakan rekening asli dengan nama yang sama sesuai dengan profil
- Deposit Menggunakan Pulsa Wajib Beserta SN Atau Nomor HP pengirim di Berita Deposit
- Multiplier Deposit Bank = 1x
- Multiplier Deposit Non Bank = 3x

[BANK]
BCA, BII / Maybank, BNI, BRI, BSI, BTN, CIMB Niaga, Niaga Syariah, Dana, Danamon, Gopay, LINK AJA, Mandiri, OCBC NISP, Ovo, Sakuku, Shopee, Bank Neo, Sea Bank, Jago, Permata, BTPN/Jenius, Bank MAS, Mandiri Syariah, BCA Syariah, QRIS

[NON BANK]
Pulsa XL, Telkomsel

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
- Minimal Withdraw Rp 25.000
- Withdraw berkelipatan Rp 1.000

Withdraw dengan E-WALLET:
Semua akun E-Wallet wajib berstatus Premium sebelum penarikan dana/Withdraw(WD).
Pastikan upgrade E-Wallet terlebih dahulu jika belum Premium.
E-Wallet Premium adalah Wajib sejak awal.

[Informasi]
-Minimal Withdraw Rp 25.000
-Withdraw berkelipatan Rp 1.000

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
                'title' => 'Case',
                'content' => 'PANDUAN: Member Baru Bertanya Informasi Situs atau Syarat Daftar

PANDUAN:
- Berikan informasi yang relevan sesuai pertanyaan user
- Jelaskan fitur dan keunggulan situs
- Jelaskan syarat & proses pendaftaran dengan benar
- Arahkan user hingga tahap registrasi

---

CARA MERESPONS:

1. PAHAMI INTENT USER
   - Jika user tanya "informasi situs" → jelaskan fitur & keunggulan
   - Jika user tanya "syarat daftar" → jelaskan data/form yang dibutuhkan

2. JELASKAN INFORMASI SITUS
   - Jenis permainan (slot, live casino, togel, dll)
   - Bonus member baru & promo
   - Deposit & withdraw cepat
   - Customer support 24 jam

3. JELASKAN SYARAT DAFTAR
   Fokus ke kebutuhan pendaftaran, bukan umur:
   - Username
   - Email
   - Nomor HP aktif
   - Rekening bank / E-wallet (status premium jika diperlukan)

4. ARAHKAN KE REGISTRASI
   - Berikan panduan daftar

5. GUNAKAN BAHASA:
   - Ramah
   - Jelas
   - Tidak kaku
   - Bersifat membantu (assistive & guiding)

---

ALUR PERCAKAPAN:

A. USER TANYA INFORMASI SITUS
→ Jelaskan fitur + keunggulan
→ Tambahkan CTA (ajak daftar)

B. USER TANYA SYARAT DAFTAR
→ Jelaskan data yang dibutuhkan
→ Tawarkan bantuan daftar

---

CONTOH RESPON (INFORMASI SITUS):
"Halo kak 👋
Di situs kami tersedia berbagai permainan seperti slot, live casino, dan togel dengan banyak pilihan pasaran.

Selain itu juga ada:
✅ Bonus member baru
✅ Promo harian menarik
✅ Deposit & withdraw cepat
✅ Support 24 jam

Kalau kakak mau, saya bisa bantu pandu cara daftar ya 😊"

---

CONTOH RESPON (SYARAT DAFTAR):
"Untuk pendaftaran cukup siapkan data berikut ya kak 😊

- Username
- Password
- Nomor HP aktif
- Rekening bank atau E-wallet (disarankan sudah premium)

Kalau kakak mau, saya bisa bantu pandu langsung proses daftarnya atau hubungkan ke CS 👍"

---

PENANGANAN JIKA KELUAR TOPIK:
Jika user mengalihkan pembahasan atau terjadi salah paham:
"Sepertinya ada sedikit salah paham ya kak 🙏
Saat ini kakak sedang menanyakan informasi terkait situs / pendaftaran.
Saya bantu jelaskan kembali ya 😊"

---

ATURAN PENTING:
- Jangan keluar dari konteks percakapan
- Jangan melakukan pengecekan nomor tanpa diminta
- Jangan fokus ke umur (kecuali ditanya langsung)
- Selalu arahkan ke registrasi (goal conversion), Jangan memberikan informasi password sebelum sukses Daftar / Register
- Gunakan soft-selling, bukan memaksa
- Tidak ada batasan umur untuk daftar, dan dapat menggunakan rekening orang lain dengan syarat data lengkap.',
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
                'content' => 'Link RTP
https://rtpcmbet95.xyz/

Link APK
https://apk.hi11office.com/CMBET(2.0.6).apk

Game Gacor / Slot Gacor / Bocoran Gacor
🔥🔥 Bocoran Slot Hari Ini 🔥🔥
#pastiamanpastihoki
#semogaberuntung

🎰 PGSOFT
- Mahjong Ways 2
- Anubis Wrath
- Candy Bonanza
- Wild Bandito
- Wild Bounty Showdown

🎰 Pragmatic Play
- Fortune of Olympus
- Gates of Olympus Dice
- Sweet Bonanza Super Scatter
- Starlight Archer 1000
- 5 Lions Megaways

🎰 Fastspin
- Mahjong Princess
- Caribbean Riches
- Golden Moon Empire
- Jungle Quest
- Pai Gow Ways

Pola Gacor / Pola Vip/Vvip / Pola
𝐏𝐎𝐋𝐀 𝐕𝐈𝐏  𝐓𝐄𝐑𝐔𝐏𝐃𝐀𝐓𝐄 𝐇𝐀𝐑𝐈 𝐈𝐍𝐈!
POLA MAHJONG WAYS 1
Turbo ✅– MANUAL Spin 10x
Turbo ✅- AUTO Spin 10x
Turbo ❌– MANUAL Spin 15x
📌𝐑𝐄𝐊𝐎𝐌𝐄𝐍𝐃𝐀𝐒𝐈 𝐁𝐄𝐓: 400 - 8.000

POLA WILD BANDITO
Turbo ✅ – Auto 30
Turbo ✅ – Manual 18
Turbo ❌ – Auto 30
Turbo ✅ – Manual 12
📌𝐑𝐄𝐊𝐎𝐌𝐄𝐍𝐃𝐀𝐒𝐈 𝐁𝐄𝐓: 400

POLA Mahjong Ways 2
Turbo ✅– MANUAL Spin 14x
Turbo ✅- AUTO Spin 30x
Turbo ❌– MANUAL Spin 10x
📌𝐑𝐄𝐊𝐎𝐌𝐄𝐍𝐃𝐀𝐒𝐈 𝐁𝐄𝐓: 400 - 8.000

📌𝐏𝐎𝐋𝐀 𝐓𝐄𝐑𝐁𝐀𝐈𝐊
📈 𝐆𝐚𝐭𝐞 𝐎𝐟 𝐎𝐥𝐲𝐦𝐩𝐮𝐬 𝟏.𝟎𝟎𝟎⚡️
-----------------------
✅❌✅ - 20 AUTO DC ON
❌✅✅ - 10 MANUAL DC OFF
❌✅✅ - 30 AUTO DC OFF
❌❌✅ - 50 MANUAL DC ON
🔔𝐁𝐔𝐘 𝐒𝐏𝐈𝐍 𝐘𝐀𝐍𝐆 𝐃𝐈𝐒𝐀𝐑𝐀𝐍𝐊𝐀𝐍 𝟐𝟎 𝐑𝐈𝐁𝐔 𝐗5
🔔𝐁𝐔𝐘 𝐒𝐏𝐈𝐍 𝐘𝐀𝐍𝐆 𝐃𝐈𝐒𝐀𝐑𝐀𝐍𝐊𝐀𝐍 6𝟎 𝐑𝐈𝐁𝐔 𝐗3',
                'source' => 'manual',
                'file_name' => null,
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
               'is_active' => $entry['is_active'],
            ]
            );
        }
    }
}
