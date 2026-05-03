<?php

namespace App\Console\Commands;

use App\Models\SystemConfig;
use Illuminate\Console\Command;

class SyncDatamodelLookup extends Command
{
    protected $signature = 'system-config:sync-datamodel';

    protected $description = 'Auto-sync all datamodel_lookup SystemConfig values';

    public function handle(): int
    {
        $configs = SystemConfig::where('source_type', 'datamodel_lookup')->get();

        foreach ($configs as $config) {
            $resolved = $config->resolveEffectiveValue();
            if ($resolved !== null) {
                $config->updateQuietly(['value' => $resolved]);
            }
        }

        SystemConfig::bumpCacheVersion();

        $this->info('Synced ' . $configs->count() . ' datamodel_lookup config(s).');

        return self::SUCCESS;
    }
}
