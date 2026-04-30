<?php

use App\Models\SystemConfig;
use App\Services\DataRetentionService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('retention:prune {--conversation-days=} {--dry-run}', function () {
    $summary = app(DataRetentionService::class)->prune(
        $this->option('conversation-days') !== null ? (int) $this->option('conversation-days') : null,
        (bool) $this->option('dry-run'),
    );

    $action = $summary['dry_run'] ? 'matched' : 'deleted';

    $this->info('Data retention pruning complete.');
    $this->line(sprintf(
        'Conversations %s: %d (customers affected: %d, cutoff: %s)',
        $action,
        $summary[$summary['dry_run'] ? 'conversations_matched' : 'conversations_deleted'],
        $summary['conversation_customers_affected'],
        $summary['conversation_cutoff'],
    ));
})->purpose('Prune expired conversations using retention settings');

// Auto-sync all datamodel_lookup SystemConfig values every hour.
Schedule::call(function () {
    $configs = SystemConfig::where('source_type', 'datamodel_lookup')->get();
    foreach ($configs as $config) {
        $resolved = $config->resolveEffectiveValue();
        if ($resolved !== null) {
            $config->updateQuietly(['value' => $resolved]);
        }
    }
    SystemConfig::bumpCacheVersion();
})->hourly()->name('system-config:sync-datamodel')->withoutOverlapping();
