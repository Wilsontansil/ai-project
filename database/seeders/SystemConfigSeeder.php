<?php

namespace Database\Seeders;

use App\Models\DataModel;
use App\Models\SystemConfig;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class SystemConfigSeeder extends Seeder
{
    /**
     * Seed default system config entries.
     */
    public function run(): void
    {
        $settingsDataModelId = DataModel::query()
            ->where('slug', 'settings')
            ->value('id');

        if ($settingsDataModelId === null) {
            return;
        }

        $hasLookupRules = Schema::hasColumn('system_configs', 'lookup_rules');
        $hasLookupField = Schema::hasColumn('system_configs', 'lookup_field');
        $hasLookupValue = Schema::hasColumn('system_configs', 'lookup_value');

        $rows = [
            [
                'key' => 'minimal_deposit_web',
                'value' => '50000',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'min deposit',
                'result_field' => 'value',
            ],
            [
                'key' => 'multiplier_deposit_bank',
                'value' => '10',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'Multiply TO',
                'result_field' => 'value',
            ],
            [
                'key' => 'multiplier_deposit_non_bank',
                'value' => '5',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'Multiply TO Non Bank',
                'result_field' => 'value',
            ],
            [
                'key' => 'event_bonus_beruntun',
                'value' => 'ACTIVE',
                'source_type' => 'manual',
                'data_model_id' => null,
                'lookup_field' => null,
                'lookup_value' => null,
                'result_field' => null,
            ],
            [
                'key' => 'bonus_ajak_teman_min_depo',
                'value' => '20000',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'bonus ajak teman min depo',
                'result_field' => 'value',
            ],
            [
                'key' => 'bonus_ajak_teman_to_depo',
                'value' => '30000',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'bonus ajak teman to depo',
                'result_field' => 'value',
            ],
            [
                'key' => 'bonus_ajak_teman_multiplier',
                'value' => '1',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'bonus ajak teman multiplier',
                'result_field' => 'value',
            ],
            [
                'key' => 'bonus_ajak_teman_between_bonus',
                'value' => '20000',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'bonus ajak teman between bonus',
                'result_field' => 'value',
            ],
            [
                'key' => 'bonus_ajak_teman_greater_bonus',
                'value' => '50000',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'bonus ajak teman greater bonus',
                'result_field' => 'value',
            ],
        ];

        foreach ($rows as $row) {
            if ($hasLookupRules) {
                $row['lookup_rules'] = $row['source_type'] === 'datamodel_lookup'
                    ? json_encode([[
                        'field' => (string) ($row['lookup_field'] ?? 'kode'),
                        'operator' => '=',
                        'value' => (string) ($row['lookup_value'] ?? ''),
                    ]], JSON_UNESCAPED_UNICODE)
                    : null;

                unset($row['lookup_field'], $row['lookup_value']);
            }

            if (! $hasLookupField && array_key_exists('lookup_field', $row)) {
                unset($row['lookup_field']);
            }

            if (! $hasLookupValue && array_key_exists('lookup_value', $row)) {
                unset($row['lookup_value']);
            }

            SystemConfig::query()->updateOrCreate(
                ['key' => $row['key']],
                $row
            );
        }
    }
}
