<?php

namespace Database\Seeders;

use App\Models\DataModel;
use Illuminate\Database\Seeder;

class DataModelSeeder extends Seeder
{
    /**
     * Seed default data model definitions.
     */
    public function run(): void
    {
        DataModel::updateOrCreate(
            ['slug' => 'players'],
            [
                'model_name' => 'players',
                'description' => 'Default players data model schema.',
                'table_name' => 'players',
                'connection_name' => 'mysqlgame',
                'fields' => [
                    'id' => 'BIGINT',
                    'name' => 'VARCHAR',
                    'username' => 'VARCHAR',
                    'email' => 'VARCHAR',
                    'hp' => 'VARCHAR',
                    'bank' => 'VARCHAR',
                    'namarek' => 'VARCHAR',
                    'norek' => 'VARCHAR',
                    'password' => 'VARCHAR',
                    'banned_at' => 'TIMESTAMP',
                    'gamepage' => 'VARCHAR',
                    'lastbet' => 'TIMESTAMP',
                    'lastdeposit' => 'VARCHAR',
                    'is_first_deposit' => 'TINYINT',
                    'gametoken' => 'VARCHAR',
                    'lastip' => 'VARCHAR',
                    // 'status' => 'VARCHAR',
                    'referral' => 'VARCHAR',
                    'ref_update_at' => 'TIMESTAMP',
                    'agents_id' => 'BIGINT',
                    'agent' => 'VARCHAR',
                    'language' => 'VARCHAR',
                    'playercode' => 'VARCHAR',
                    'balance' => 'DECIMAL(14,3)',
                    'to' => 'DECIMAL(14,3)',
                    'winloss' => 'DECIMAL(14,3)',
                    'playertoken' => 'VARCHAR',
                    'WDOTP' => 'VARCHAR',
                    'remark' => 'TEXT',
                    'lastlogin' => 'TIMESTAMP',
                    'lastactivity' => 'TIMESTAMP',
                    'targetTO' => 'DECIMAL(14,3)',
                    'categoryTO' => 'VARCHAR',
                    'getbonustime' => 'TIMESTAMP',
                    'totalbonus' => 'DECIMAL(14,3)',
                    'flag' => 'TINYINT',
                    'startdateTO' => 'TIMESTAMP',
                    'useragent' => 'TEXT',
                    'fingerprint' => 'VARCHAR',
                    'browser' => 'VARCHAR',
                    'OS' => 'VARCHAR',
                    'device' => 'VARCHAR',
                    'devicefamily' => 'VARCHAR',
                    'referer' => 'VARCHAR',
                    'bonuswelcome_eventid' => 'BIGINT',
                    'bonuswelcome_valid' => 'TINYINT',
                    'freespin_balance_counter' => 'INT',
                    'remember_token' => 'VARCHAR',
                    'apk' => 'TINYINT',
                    'attention' => 'TINYINT',
                    'is_sync' => 'INT',
                    'request_id' => 'VARCHAR',
                    'created_at' => 'TIMESTAMP',
                    'updated_at' => 'TIMESTAMP',
                ],
            ]
        );
    }
}
