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

        $tools = [
            [
                'tool_name' => '_bot_config',
                'display_name' => 'Bot Config',
                'description' => 'General bot configuration',
                'slug' => '_bot-config',
                'is_enabled' => false,
                'data_model_id' => null,
                'parameters' => null,
                'keywords' => null,
                'missing_message' => null,
                'information_text' => null,
                'meta' => ['bot_name' => 'xoneBot'],
            ],
            [
                'tool_name' => 'resetPassword',
                'display_name' => 'Reset Password',
                'description' => 'Reset user password after account data verification',
                'slug' => 'reset-password',
                'is_enabled' => true,
                'data_model_id' => $playersDataModelId,
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
                            'player_id' => '$player->id',
                        ],
                        'expected_response' => [
                            'status' => 200,
                            'message' => 'Success',
                            'data' => (object) [],
                        ],
                    ],
                ],
                'keywords' => ['reset password', 'resetpass', 'kata sandi', 'password'],
                'missing_message' => "Untuk reset password, mohon kirim data berikut:\nUsername(username) :\nNama rekening(namarek) :\nNomor rekening(norek) :\nNama Bank(bank) :",
                'information_text' => null,
                'meta' => null,
            ],
            [
                'tool_name' => 'checkSuspend',
                'display_name' => 'Check Suspend',
                'description' => 'Check if a player account is suspended',
                'slug' => 'check-suspend',
                'is_enabled' => true,
                'data_model_id' => $playersDataModelId,
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'username' => ['type' => 'string', 'description' => 'Username to check suspend status'],
                    ],
                    'required' => ['username'],
                ],
                'keywords' => ['suspend', 'check suspend', 'status', 'suspended', 'cek suspend', 'suspend status'],
                'missing_message' => 'Untuk cek status suspend, mohon kirim username dengan format: username: namakamu',
                'information_text' => null,
                'meta' => null,
            ],
            [
                'tool_name' => 'register',
                'display_name' => 'Register',
                'description' => 'Register a new player account. Requires username, email, phone number (telepon/hp), bank name, account holder name (nama rekening), and account number (nomor rekening).',
                'slug' => 'register',
                'is_enabled' => true,
                'data_model_id' => $playersDataModelId,
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
                'keywords' => ['register', 'daftar', 'registrasi', 'buat akun', 'sign up', 'signup', 'mendaftar'],
                'missing_message' => "Untuk registrasi, mohon kirim data berikut:\nUsername(username) :\nEmail(email) :\nTelepon(hp) :\nBank(bank) :\nNama Rekening(namarek) :\nNomor Rekening(norek) :",
                'information_text' => null,
                'meta' => null,
            ],
            [
                'tool_name' => 'game_gacor',
                'display_name' => 'Game Gacor',
                'description' => 'the latest Bocoran Slot Gacor for today. Only take information from the provided list. Do not create new content',
                'slug' => 'game-gacor',
                'is_enabled' => true,
                'data_model_id' => null,
                'parameters' => null,
                'keywords' => ['game gacor', 'slot gacor', 'bocoran slot'],
                'missing_message' => null,
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
                'is_enabled' => true,
                'data_model_id' => null,
                'parameters' => null,
                'keywords' => ['pola gacor', 'pola vip', 'pola vvip', 'pola mantap', 'pola terbaik'],
                'missing_message' => null,
                'information_text' => "𝐏𝐎𝐋𝐀 𝐕𝐈𝐏  𝐓𝐄𝐑𝐔𝐏𝐃𝐀𝐓𝐄 𝐇𝐀𝐑𝐈 𝐈𝐍𝐈!\nPOLA MAHJONG WAYS 1\nTurbo ✅– MANUAL Spin 10x\nTurbo ✅- AUTO Spin 10x\nTurbo ❌– MANUAL Spin 15x\n📌𝐑𝐄𝐊𝐎𝐌𝐄𝐍𝐃𝐀𝐒𝐈 𝐁𝐄𝐓: 400 - 8.000\n\nPOLA WILD BANDITO\nTurbo ✅ – Auto 30\nTurbo ✅ – Manual 18\nTurbo ❌ – Auto 30\nTurbo ✅ – Manual 12\n📌𝐑𝐄𝐊𝐎𝐌𝐄𝐍𝐃𝐀𝐒𝐈 𝐁𝐄𝐓: 400\n\nPOLA Mahjong Ways 2\nTurbo ✅– MANUAL Spin 14x\nTurbo ✅- AUTO Spin 30x\nTurbo ❌– MANUAL Spin 10x\n📌𝐑𝐄𝐊𝐎𝐌𝐄𝐍𝐃𝐀𝐒𝐈 𝐁𝐄𝐓: 400 - 8.000\n\n📌𝐏𝐎𝐋𝐀 𝐓𝐄𝐑𝐁𝐀𝐈𝐊 \n📈 𝐆𝐚𝐭𝐞 𝐎𝐟 𝐎𝐥𝐲𝐦𝐩𝐮𝐬 𝟏.𝟎𝟎𝟎⚡️\n-----------------------\n✅❌✅ - 20 AUTO DC ON\n❌✅✅ - 10 MANUAL DC OFF\n❌✅✅ - 30 AUTO DC OFF\n❌❌✅ - 50 MANUAL DC ON\n🔔𝐁𝐔𝐘 𝐒𝐏𝐈𝐍 𝐘𝐀𝐍𝐆 𝐃𝐈𝐒𝐀𝐑𝐀𝐍𝐊𝐀𝐍 𝟐𝟎 𝐑𝐈𝐁𝐔 𝐗5\n🔔𝐁𝐔𝐘 𝐒𝐏𝐈𝐍 𝐘𝐀𝐍𝐆 𝐃𝐈𝐒𝐀𝐑𝐀𝐍𝐊𝐀𝐍 6𝟎 𝐑𝐈𝐁𝐔 𝐗3",
                'meta' => null,
            ],
            [
                'tool_name' => 'bonus',
                'display_name' => 'Bonus',
                'description' => 'detailed information about the bonus claims. Answer any questions members may have regarding how to claim bonuses, eligibility, and any specific conditions or requirements',
                'slug' => 'bonus',
                'is_enabled' => true,
                'data_model_id' => null,
                'parameters' => null,
                'keywords' => ['bonus claim', 'klaim bonus', 'bonus', 'bonus reward'],
                'missing_message' => null,
                'information_text' => ['Halo Kak! Sebelum mulai bermain, jangan lupa untuk melakukan klaim bonusnya terlebih dahulu melalui menu Reward ya'],
                'meta' => null,
            ],
            [
                'tool_name' => 'link_rtp',
                'display_name' => 'Link RTP',
                'description' => 'Provide information about RTP links for online slots or games. Answer any questions regarding how RTP is calculated, where to find RTP links, and how it affects gameplay or chances of winning.',
                'slug' => 'link-rtp',
                'is_enabled' => true,
                'data_model_id' => null,
                'parameters' => null,
                'keywords' => ['link rtp', 'rtp', 'link anti rungkat'],
                'missing_message' => null,
                'information_text' => ["klik link untuk cek rtp kami disini dengan winrate game tertinggi kak 🙂\n- realtime - akurat - anti rungkat -\n\nhttps://rtpbigmsg77.xyz\n\ndan sudah tersedia jadwal dan pola gacor nya kak"],
                'meta' => null,
            ],
            [
                'tool_name' => 'link_apk',
                'display_name' => 'Link APK',
                'description' => 'Provide a link to download the APK for the specified app. Ensure the link is from a trusted source to guarantee security.',
                'slug' => 'link-apk',
                'is_enabled' => true,
                'data_model_id' => null,
                'parameters' => null,
                'keywords' => ['link apk', 'download apk', 'apk', 'url apk'],
                'missing_message' => null,
                'information_text' => null,
                'meta' => null,
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
