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
     * Get the active agent configured in backoffice, or fall back to first agent.
     */
    public static function getActive(): ?self
    {
        $setting = \App\Models\ToolSetting::query()
            ->where('tool_name', '_active_agent')
            ->first();

        if ($setting) {
            $meta = is_array($setting->meta) ? $setting->meta : [];
            $agentId = $meta['agent_id'] ?? null;

            if ($agentId !== null) {
                $agent = static::find($agentId);
                if ($agent) {
                    return $agent;
                }
            }
        }

        return static::first();
    }
}
