<?php

namespace Database\Seeders;

use App\Models\DataModel;
use App\Models\SystemConfig;
use Illuminate\Database\Seeder;

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
                'value' => '3',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'Multiply TO Non Bank',
                'result_field' => 'value',
            ],
        ];

        foreach ($rows as $row) {
            SystemConfig::query()->updateOrCreate(
                ['key' => $row['key']],
                $row
            );
        }
    }
}
