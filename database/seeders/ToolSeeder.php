<?php

namespace Database\Seeders;

use App\Models\DataModel;
use App\Models\Tool;
use Illuminate\Database\Seeder;

class ToolSeeder extends Seeder
{
    public function run(): void
    {
        $playersModel = DataModel::query()->where('slug', 'players')->first();
        $playersDataModelId = $playersModel?->id;

        $depositModel = DataModel::query()->where('slug', 'deposit')->first();
        $withdrawModel = DataModel::query()->where('slug', 'withdraw')->first();

        $tools = [
            // ─── Internal config (no type) ───
            [
                'tool_name' => '_bot_config',
                'display_name' => 'Bot Config',
                'description' => 'General bot configuration',
                'slug' => '_bot-config',
                'type' => 'info',
                'is_enabled' => false,
                'data_model_id' => null,
                'parameters' => null,
                'endpoints' => null,
                'keywords' => null,
                'tool_rules' => null,
                'information_text' => null,
                'meta' => ['bot_name' => 'xoneBot'],
            ],

            // ─── UPDATE type tools (API endpoint) ───
            [
                'tool_name' => 'resetPassword',
                'display_name' => 'Reset Password',
                'description' => 'Reset user password after account data verification',
                'slug' => 'reset-password',
                'type' => 'update',
                'is_enabled' => true,
                'data_model_id' => null,
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'username' => ['type' => 'string', 'description' => 'Username akun'],
                        'namarek' => ['type' => 'string', 'description' => 'Nama rekening'],
                        'norek' => ['type' => 'string', 'description' => 'Nomor rekening'],
                        'bank' => ['type' => 'string', 'description' => 'Nama bank'],
                    ],
                    'required' => ['username', 'namarek', 'norek', 'bank'],
                ],
                'endpoints' => [
                    'endpoint' => [
                        'route' => '/resetpassword',
                        'body' => [
                            'username' => '',
                            'namarek' => '',
                            'norek' => '',
                            'bank' => '',
                        ],
                        'expected_response' => [
                            'status' => 200,
                            'message' => 'Success',
                            'data' => (object) [],
                        ],
                    ],
                ],
                'keywords' => ['reset password', 'resetpass', 'kata sandi', 'password'],
                'tool_rules' => "- Minta semua data sekaligus dalam satu pesan yang rapi: username, nama rekening, nomor rekening, nama bank\n- Format permintaan data harus dalam list\n- Jangan eksekusi tool sebelum SEMUA data terkumpul lengkap\n- Setelah berhasil, infokan bahwa password sudah direset dan minta player login ulang\n- Jika data tidak ditemukan, minta user periksa kembali semua data yang diinput",
                'information_text' => null,
                'meta' => null,
            ],
            [
                'tool_name' => 'register',
                'display_name' => 'Register',
                'description' => 'Register a new player account. Requires username, email, phone number (telepon/hp), bank name, account holder name (nama rekening), and account number (nomor rekening).',
                'slug' => 'register',
                'type' => 'update',
                'is_enabled' => true,
                'data_model_id' => null,
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'username' => ['type' => 'string', 'description' => 'Username untuk akun baru'],
                        'email' => ['type' => 'string', 'description' => 'Email untuk akun baru'],
                        'hp' => ['type' => 'string', 'description' => 'Nomor telepon/HP'],
                        'bank' => ['type' => 'string', 'description' => 'Nama bank'],
                        'namarek' => ['type' => 'string', 'description' => 'Nama rekening'],
                        'norek' => ['type' => 'string', 'description' => 'Nomor rekening'],
                    ],
                    'required' => ['username', 'email', 'hp', 'bank', 'namarek', 'norek'],
                ],
                'endpoints' => [
                    'endpoint' => [
                        'route' => '/register',
                        'body' => [
                            'username' => '',
                            'email' => '',
                            'hp' => '',
                            'bank' => '',
                            'namarek' => '',
                            'norek' => '',
                        ],
                        'expected_response' => [
                            'status' => 200,
                            'message' => 'Success',
                            'data' => (object) [],
                        ],
                    ],
                ],
                'keywords' => ['register', 'daftar', 'registrasi', 'buat akun', 'sign up', 'signup', 'mendaftar'],
                'tool_rules' => "- Minta semua data sekaligus dalam satu pesan: username, email, telepon/hp, bank, nama rekening, nomor rekening\n- Validasi format: email harus mengandung @, hp harus numerik, norek harus numerik\n- Jangan eksekusi sebelum semua 6 data lengkap\n- Setelah berhasil registrasi, ucapkan selamat dan berikan panduan login",
                'information_text' => null,
                'meta' => null,
            ],

            // ─── GET type tools (DataModel lookup) ───
            [
                'tool_name' => 'checkSuspend',
                'display_name' => 'Check Suspend',
                'description' => 'Check if a player account is suspended',
                'slug' => 'check-suspend',
                'type' => 'get',
                'is_enabled' => true,
                'data_model_id' => $playersDataModelId,
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'username' => ['type' => 'string', 'description' => 'Username to check suspend status'],
                    ],
                    'required' => ['username'],
                ],
                'endpoints' => null,
                'keywords' => ['suspend', 'check suspend', 'status', 'suspended', 'cek suspend', 'suspend status'],
                'tool_rules' => "- Minta username terlebih dahulu jika belum diberikan\n- Cek field 'banned_at': jika null berarti TIDAK suspend, jika ada tanggal berarti SUSPEND\n- Jika suspend, infokan bahwa akunnya sedang dalam status suspend dan sarankan hubungi CS via livechat untuk info lebih lanjut\n- Jika tidak suspend, infokan bahwa akun dalam kondisi normal/aktif",
                'information_text' => null,
                'meta' => null,
            ],
            [
                'tool_name' => 'toStatus',
                'display_name' => 'TurnOver Status',
                'description' => 'Provide information about TurnOver (TO) status for a player. Uses "to" and "targetTo" fields from the Player model.',
                'slug' => 'to-status',
                'type' => 'get',
                'is_enabled' => true,
                'data_model_id' => $playersDataModelId,
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'username' => ['type' => 'string', 'description' => 'Username untuk cek status TurnOver'],
                    ],
                    'required' => ['username'],
                ],
                'endpoints' => null,
                'keywords' => ['to', 'turnover', 'status to', 'jumlah to', 'target to'],
                'tool_rules' => "- Minta username terlebih dahulu jika belum diberikan\n- Hanya tampilkan informasi field 'to' (jumlah TO saat ini) dan 'targetTo' (target TO yang harus dicapai)\n- Jangan tampilkan field lain dari data player\n- Hitung sisa TO yang harus dicapai: targetTo - to\n- Jika TO sudah mencapai atau melebihi target, infokan bahwa TO sudah terpenuhi dan player bisa melakukan withdraw\n- Jika TO belum tercapai, infokan sisa TO yang harus dipenuhi sebelum bisa withdraw\n- Sarankan player untuk terus bermain agar target TO cepat terpenuhi",
                'information_text' => null,
                'meta' => null,
            ],

            // ─── INFO type tools (static information) ───
            [
                'tool_name' => 'game_gacor',
                'display_name' => 'Game Gacor',
                'description' => 'the latest Bocoran Slot Gacor for today. Only take information from the provided list. Do not create new content',
                'slug' => 'game-gacor',
                'type' => 'info',
                'is_enabled' => true,
                'data_model_id' => null,
                'parameters' => null,
                'endpoints' => null,
                'keywords' => ['game gacor', 'slot gacor', 'bocoran slot'],
                'tool_rules' => "- Jawab HANYA dari information text yang tersedia, jangan buat konten baru\n- Gunakan emoji dan format menarik untuk presentasi\n- Jika ditanya game yang tidak ada di list, bilang tidak tersedia dan tawarkan yang ada",
                'information_text' => [
                    "🔥🔥bocoran slot hari ini🔥🔥\n#pastiamanpastihoki\n#semogaberuntung\n\nspadegaming\n-clash of the giants\n-royale vegas\n-brothers kingdom 2\n-gold phanter maxways\n-roma\n\npragmatic play\n-gates of olympus 1000\n-mahjong wins 2 100.000x\n-mahjong wins 3 black scatter\n-sweet bonanza super scatter\n-triple pot gold\n\npgsoft\n-Anubis wrath\n-buffalo win\n-mahjong ways\n-sun & moon\n-cash mania",
                    "🔥🔥bocoran slot hari ini🔥🔥\n#pastiamanpastihoki\n#semogaberuntung\n\nnolimit city\n- poison Eve\n- The Crypt\n- bounty hunters\n- disturbed\n- dead wood\n\npragmatic play\n- mahjong wins 3 black scatter\n- gates of Olympus 1000\n- gates of gatotkaca 1000\n- starlight princess 1000\n- Aztec gems\n\npgsoft\n- candy burst\n- trasueres of aztec\n- crypto gold\n- emoji riches\n- mafia mayhem",
                    "🔥🔥bocoran slot hari ini🔥🔥\n#pastiamanpastihoki\n#semogaberuntung\n\nmicrogaming\n- candy rush wilds 2\n- lucky twins wilds\n- 10000 fortunes\n- 777 royal wheel\n- 22 skulls of the dead\n\npragmatic play\n- starlight christmas\n- gates of olympus super scatter\n- sweet bonanza\n- triple pot gold\n- mahjong wins 3 black scatter\n\npgsoft\n- mahjong ways 2\n- wild bounty showdown\n- wild bandito\n- wild ape\n- treasure of Aztec",
                    "🔥🔥bocoran slot hari ini🔥🔥\n#pastiamanpastihoki\n#semogaberuntung\n\nspadegaming\n- clash of the giants\n- fury max lucky road\n- royale vegas\n- roma\n- sugar party\n\npragmatic play\n- gates of olympus super scatter\n- gates of gatotkaca super scatter\n- pyramid bonanza\n- sweet bonanza\n- sugar rush 1000\n\npgsoft\n- bakery bonanza\n- buffalo win\n- Alibaba's cave of fortune\n- candy bonanza\n- bikini paradise",
                    "🔥🔥bocoran slot hari ini🔥🔥\n#pastiamanpastihoki\n#semogaberuntung\n\nwonwon\n- olympus 8000\n- mahjong mania\n- zeus\n- ganesha\n- dragon mystery\n\npragmatic play\n- forge of olympus\n- starlight princess 1000\n- pyramid bonanza\n- sweet bonanza\n- wild booster\n\npgsoft\n- mahjong ways\n- buffalo win\n- captain's bounty\n- candy bonanza\n- the great icescape",
                    "🔥🔥bocoran slot hari ini🔥🔥\n#pastiamanpastihoki\n#semogaberuntung\n\nfastspin\n- mahjong princess\n- Caribbean riches\n- golden moon empire\n- jungle quest\n- pai gow ways\n\npragmatic play\n- fortune of olympus\n- gates of Olympus dice\n- sweet bonanza super scatter\n- starlight archer 1000\n- 5 lions megaways\n\npgsoft\n- mahjong ways 2\n- Anubis wrath\n- candy bonanza\n- wild bandito\n- wild bounty showdown",
                ],
                'meta' => null,
            ],
            [
                'tool_name' => 'pola_gacor',
                'display_name' => 'Pola Gacor',
                'description' => 'the latest Pola Gacor for today. Only take information from the provided list. Do not create new content',
                'slug' => 'pola-gacor',
                'type' => 'info',
                'is_enabled' => true,
                'data_model_id' => null,
                'parameters' => null,
                'endpoints' => null,
                'keywords' => ['pola gacor', 'pola vip', 'pola vvip', 'pola mantap', 'pola terbaik'],
                'tool_rules' => "- Jawab HANYA dari information text yang tersedia, jangan buat pola baru\n- Tampilkan pola dengan format asli termasuk emoji ✅❌\n- Jika ditanya game yang tidak ada polanya, bilang belum tersedia",
                'information_text' => "𝐏𝐎𝐋𝐀 𝐕𝐈𝐏  𝐓𝐄𝐑𝐔𝐏𝐃𝐀𝐓𝐄 𝐇𝐀𝐑𝐈 𝐈𝐍𝐈!\nPOLA MAHJONG WAYS 1\nTurbo ✅– MANUAL Spin 10x\nTurbo ✅- AUTO Spin 10x\nTurbo ❌– MANUAL Spin 15x\n📌𝐑𝐄𝐊𝐎𝐌𝐄𝐍𝐃𝐀𝐒𝐈 𝐁𝐄𝐓: 400 - 8.000\n\nPOLA WILD BANDITO\nTurbo ✅ – Auto 30\nTurbo ✅ – Manual 18\nTurbo ❌ – Auto 30\nTurbo ✅ – Manual 12\n📌𝐑𝐄𝐊𝐎𝐌𝐄𝐍𝐃𝐀𝐒𝐈 𝐁𝐄𝐓: 400\n\nPOLA Mahjong Ways 2\nTurbo ✅– MANUAL Spin 14x\nTurbo ✅- AUTO Spin 30x\nTurbo ❌– MANUAL Spin 10x\n📌𝐑𝐄𝐊𝐎𝐌𝐄𝐍𝐃𝐀𝐒𝐈 𝐁𝐄𝐓: 400 - 8.000\n\n📌𝐏𝐎𝐋𝐀 𝐓𝐄𝐑𝐁𝐀𝐈𝐊 \n📈 𝐆𝐚𝐭𝐞 𝐎𝐟 𝐎𝐥𝐲𝐦𝐩𝐮𝐬 𝟏.𝟎𝟎𝟎⚡️\n-----------------------\n✅❌✅ - 20 AUTO DC ON\n❌✅✅ - 10 MANUAL DC OFF\n❌✅✅ - 30 AUTO DC OFF\n❌❌✅ - 50 MANUAL DC ON\n🔔𝐁𝐔𝐘 𝐒𝐏𝐈𝐍 𝐘𝐀𝐍𝐆 𝐃𝐈𝐒𝐀𝐑𝐀𝐍𝐊𝐀𝐍 𝟐𝟎 𝐑𝐈𝐁𝐔 𝐗5\n🔔𝐁𝐔𝐘 𝐒𝐏𝐈𝐍 𝐘𝐀𝐍𝐆 𝐃𝐈𝐒𝐀𝐑𝐀𝐍𝐊𝐀𝐍 6𝟎 𝐑𝐈𝐁𝐔 𝐗3",
                'meta' => null,
            ],
            [
                'tool_name' => 'bonus',
                'display_name' => 'Bonus',
                'description' => 'detailed information about the bonus claims. Answer any questions members may have regarding how to claim bonuses, eligibility, and any specific conditions or requirements',
                'slug' => 'bonus',
                'type' => 'info',
                'is_enabled' => true,
                'data_model_id' => null,
                'parameters' => null,
                'endpoints' => null,
                'keywords' => ['bonus claim', 'klaim bonus', 'bonus', 'bonus reward'],
                'tool_rules' => "- Infokan cara klaim bonus melalui menu Reward\n- Jika ditanya bonus spesifik, jelaskan bahwa semua bonus bisa diklaim dari menu Reward sebelum bermain\n- Gunakan tone ramah dan semangat",
                'information_text' => ['Halo Kak! Sebelum mulai bermain, jangan lupa untuk melakukan klaim bonusnya terlebih dahulu melalui menu Reward ya'],
                'meta' => null,
            ],
            [
                'tool_name' => 'link_rtp',
                'display_name' => 'Link RTP',
                'description' => 'Provide information about RTP links for online slots or games. Answer any questions regarding how RTP is calculated, where to find RTP links, and how it affects gameplay or chances of winning.',
                'slug' => 'link-rtp',
                'type' => 'info',
                'is_enabled' => true,
                'data_model_id' => null,
                'parameters' => null,
                'endpoints' => null,
                'keywords' => ['link rtp', 'rtp', 'link anti rungkat'],
                'tool_rules' => "- Berikan link RTP dari information text\n- Jelaskan bahwa link tersebut menampilkan winrate game tertinggi secara realtime\n- Jangan buat atau tebak link sendiri",
                'information_text' => ["klik link untuk cek rtp kami disini dengan winrate game tertinggi kak 🙂\n- realtime - akurat - anti rungkat -\n\nhttps://rtpbigmsg77.xyz\n\ndan sudah tersedia jadwal dan pola gacor nya kak"],
                'meta' => null,
            ],
            [
                'tool_name' => 'link_apk',
                'display_name' => 'Link APK',
                'description' => 'Provide a link to download the APK for the specified app. Ensure the link is from a trusted source to guarantee security.',
                'slug' => 'link-apk',
                'type' => 'info',
                'is_enabled' => true,
                'data_model_id' => null,
                'parameters' => null,
                'endpoints' => null,
                'keywords' => ['link apk', 'download apk', 'apk', 'url apk'],
                'tool_rules' => "- Berikan link APK dari information text\n- Jangan buat atau tebak link sendiri, gunakan HANYA link yang tersedia\n- Ajak user untuk mendaftar atau bermain dengan nada ramah dan semangat",
                'information_text' => ["silahkan akses link apk untuk daftar ataupun bermain di bigmsg ya kak\n\nhttps://apk.hi11office.com/BIGMSG(2.0.6).apk\n\nselamat bermain dan semoga beruntung"],
                'meta' => null,
            ],

            // ─── GET MULTIPLE type tools ───
            [
                'tool_name' => 'BonusCashback',
                'display_name' => 'Bonus Cashback',
                'description' => 'Provide information about bonus cashback. Compare total "amount" on Deposit and Withdraw model in LAST WEEK where status is "accept". If total Withdraw amount > total Deposit amount, the player has no cashback.',
                'slug' => 'bonus-cashback',
                'type' => 'get_multiple',
                'is_enabled' => true,
                'data_model_id' => null,
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'username' => ['type' => 'string', 'description' => 'Username pemain'],
                    ],
                    'required' => ['username'],
                ],
                'endpoints' => null,
                'keywords' => ['cashback', 'bonus cashback', 'cek cashback'],
                'tool_rules' => "- Minta username terlebih dahulu jika belum diberikan\n- Bandingkan total amount dari Deposit dan Withdraw minggu lalu (Senin-Minggu) dengan status \"accept\"\n- Periode minggu lalu = Senin 00:00:00 sampai Minggu 23:59:59 dari minggu sebelumnya\n- Jika total Withdraw > total Deposit, berarti pemain TIDAK mendapatkan cashback (sudah profit/menang)\n- Jika total Deposit > total Withdraw, pemain BERHAK mendapatkan cashback dari selisihnya\n- Jika tidak ada data deposit atau withdraw, sampaikan bahwa tidak ada transaksi di minggu lalu\n- Tampilkan: total deposit, total withdraw, selisih, dan status kelayakan cashback\n- Format angka dalam mata uang (gunakan separator ribuan)\n- Jangan simpulkan rate/persentase cashback, hanya sampaikan data perbandingan dan kelayakan",
                'information_text' => null,
                'meta' => [
                    'data_model_ids' => array_filter([
                        $depositModel?->id,
                        $withdrawModel?->id,
                    ]),
                ],
            ],
        ];

        foreach ($tools as $tool) {
            Tool::query()->updateOrCreate(
                ['tool_name' => $tool['tool_name']],
                $tool
            );
        }
    }
}
