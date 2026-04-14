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
                    'id' => ['type' => 'BIGINT', 'required' => false],
                    'name' => ['type' => 'VARCHAR', 'required' => false],
                    'username' => ['type' => 'VARCHAR', 'required' => true],
                    'email' => ['type' => 'VARCHAR', 'required' => false],
                    'hp' => ['type' => 'VARCHAR', 'required' => false],
                    'bank' => ['type' => 'VARCHAR', 'required' => false],
                    'namarek' => ['type' => 'VARCHAR', 'required' => false],
                    'norek' => ['type' => 'VARCHAR', 'required' => false],
                    'password' => ['type' => 'VARCHAR', 'required' => false],
                    'banned_at' => ['type' => 'TIMESTAMP', 'required' => false],
                    'gamepage' => ['type' => 'VARCHAR', 'required' => false],
                    'lastbet' => ['type' => 'TIMESTAMP', 'required' => false],
                    'lastdeposit' => ['type' => 'VARCHAR', 'required' => false],
                    'is_first_deposit' => ['type' => 'TINYINT', 'required' => false],
                    'gametoken' => ['type' => 'VARCHAR', 'required' => false],
                    'lastip' => ['type' => 'VARCHAR', 'required' => false],
                    // 'status' => ['type' => 'VARCHAR', 'required' => false],
                    'referral' => ['type' => 'VARCHAR', 'required' => false],
                    'ref_update_at' => ['type' => 'TIMESTAMP', 'required' => false],
                    'agents_id' => ['type' => 'BIGINT', 'required' => false],
                    'agent' => ['type' => 'VARCHAR', 'required' => false],
                    'language' => ['type' => 'VARCHAR', 'required' => false],
                    'playercode' => ['type' => 'VARCHAR', 'required' => false],
                    'balance' => ['type' => 'DECIMAL(14,3)', 'required' => false],
                    'to' => ['type' => 'DECIMAL(14,3)', 'required' => false],
                    'winloss' => ['type' => 'DECIMAL(14,3)', 'required' => false],
                    'playertoken' => ['type' => 'VARCHAR', 'required' => false],
                    'WDOTP' => ['type' => 'VARCHAR', 'required' => false],
                    'remark' => ['type' => 'TEXT', 'required' => false],
                    'lastlogin' => ['type' => 'TIMESTAMP', 'required' => false],
                    'lastactivity' => ['type' => 'TIMESTAMP', 'required' => false],
                    'targetTO' => ['type' => 'DECIMAL(14,3)', 'required' => false],
                    'categoryTO' => ['type' => 'VARCHAR', 'required' => false],
                    'getbonustime' => ['type' => 'TIMESTAMP', 'required' => false],
                    'totalbonus' => ['type' => 'DECIMAL(14,3)', 'required' => false],
                    'flag' => ['type' => 'TINYINT', 'required' => false],
                    'startdateTO' => ['type' => 'TIMESTAMP', 'required' => false],
                    'useragent' => ['type' => 'TEXT', 'required' => false],
                    'fingerprint' => ['type' => 'VARCHAR', 'required' => false],
                    'browser' => ['type' => 'VARCHAR', 'required' => false],
                    'OS' => ['type' => 'VARCHAR', 'required' => false],
                    'device' => ['type' => 'VARCHAR', 'required' => false],
                    'devicefamily' => ['type' => 'VARCHAR', 'required' => false],
                    'referer' => ['type' => 'VARCHAR', 'required' => false],
                    'bonuswelcome_eventid' => ['type' => 'BIGINT', 'required' => false],
                    'bonuswelcome_valid' => ['type' => 'TINYINT', 'required' => false],
                    'freespin_balance_counter' => ['type' => 'INT', 'required' => false],
                    'remember_token' => ['type' => 'VARCHAR', 'required' => false],
                    'apk' => ['type' => 'TINYINT', 'required' => false],
                    'attention' => ['type' => 'TINYINT', 'required' => false],
                    'is_sync' => ['type' => 'INT', 'required' => false],
                    'request_id' => ['type' => 'VARCHAR', 'required' => false],
                    'created_at' => ['type' => 'TIMESTAMP', 'required' => false],
                    'updated_at' => ['type' => 'TIMESTAMP', 'required' => false],
                ],
            ]
        );

        DataModel::updateOrCreate(
            ['slug' => 'settings'],
            [
                'model_name' => 'Setting',
                'description' => 'Settings data model schema.',
                'table_name' => 'settings',
                'connection_name' => 'mysqlgame',
                'fields' => [
                    'id' => ['type' => 'bigint(20) UNSIGNED', 'required' => false],
                    'kode' => ['type' => 'varchar(125)', 'required' => true],
                    'value' => ['type' => 'mediumtext', 'required' => false],
                    'agent' => ['type' => 'varchar(125)', 'required' => false],
                    'created_at' => ['type' => 'timestamp', 'required' => false],
                    'updated_at' => ['type' => 'timestamp', 'required' => false],
                ],
            ]
        );
    }
}
