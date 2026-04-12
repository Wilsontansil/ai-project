<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Agent extends Model
{
    protected $connection = 'mysqlgame';

    protected $fillable = [
        'kode',
    ];

    /**
     * Get the active agent from env configuration (AGENT_ID).
     */
    public static function getActive(): ?self
    {
        $agentId = (int) ProjectSetting::getValue('agent_id', config('services.agent.id', 1));

        return static::find($agentId);
    }
}
