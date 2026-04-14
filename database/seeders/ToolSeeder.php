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
                'meta' => [
                    'icon' => 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z',
                ],
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
                'meta' => [
                    'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
                ],
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
                'meta' => [
                    'icon' => 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
                ],
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
                'information_text' => "🔥🔥bocoran slot hari ini🔥🔥\n#pastiamanpastihoki\n#semogaberuntung\n\n fastspin\n- royal house \n- roma\n- poker ways\n- fruits mania\n- neko riches\n\npragmatic play \n- mahjong ways 3 black scatter\n- starlight princes 1000\n- gates of Olympus 1000\n- gates of gatotkaca 1000\n- sweet bonanza 1000\n\npgsoft\n- mahjong ways\n- treasures of aztec\n- Jurassic kingdom\n- wild bandito\n- wild ape",
                'meta' => [
                    'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',
                ],
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
                'meta' => [
                    'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
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
