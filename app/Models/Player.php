<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    protected $connection = 'mysqlgame';

    protected $fillable = [
        'name',
        'username',
        'email',
        'hp',
        'bank',
        'namarek',
        'norek',
        'password',
        'banned_at',
        'gampage',
        'lastbet',
        'lastdeposit',
        'is_first_deposit',
        'gametoken',
        'lastip',
        'status',
        'referral',
        'ref_update_at',
        'agents_id',
        'agent',
        'language',
        'playercode',
        'balance',
        'winloss',
        'player_token',
        'WDOTP',
        'remark',
        'lastlogin',
        'lastactivity',
        'targetTO',
        'categoryTO',
        'getbonustime',
        'totalbonus',
        'flag',
        'startdateto',
        'useragent',
        'fingerprint',
        'browser',
        'os',
        'device',
        'devicefamily',
        'referer',
        'bonuswelcome_eventid',
        'bonuswelcome_valid',
        'freespin_balance_counter',
        'remember_token',
        'apk',
        'attention',
        'is_sync',
        'request_id',
    ];

    protected $casts = [
        'balance' => 'decimal:3',
        'winloss' => 'decimal:3',
        'targetTO' => 'decimal:3',
        'totalbonus' => 'decimal:3',

        'is_first_deposit' => 'boolean',
        'flag' => 'boolean',
        'apk' => 'boolean',
        'attention' => 'boolean',
        'is_sync' => 'boolean',

        'banned_at' => 'datetime',
        'lastbet' => 'datetime',
        'lastlogin' => 'datetime',
        'lastactivity' => 'datetime',
        'getbonustime' => 'datetime',
        'startdateto' => 'datetime',
        'ref_update_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
