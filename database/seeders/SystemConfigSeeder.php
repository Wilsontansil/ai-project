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
                'key' => 'dep_min',
                'value' => '50000',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'min deposit',
                'result_field' => 'value',
            ],
            [
                'key' => 'dep_mul_bank',
                'value' => '10',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'Multiply TO',
                'result_field' => 'value',
            ],
            [
                'key' => 'dep_mul_nonbank',
                'value' => '5',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'Multiply TO Non Bank',
                'result_field' => 'value',
            ],
            [
                'key' => 'ev_brt',
                'value' => 'ACTIVE',
                'source_type' => 'manual',
                'data_model_id' => null,
                'lookup_field' => null,
                'lookup_value' => null,
                'result_field' => null,
            ],
            [
                'key' => 'ref_min',
                'value' => '20000',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'bonus ajak teman min depo',
                'result_field' => 'value',
            ],
            [
                'key' => 'ref_to',
                'value' => '30000',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'bonus ajak teman to depo',
                'result_field' => 'value',
            ],
            [
                'key' => 'ref_mul',
                'value' => '1',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'bonus ajak teman multiplier',
                'result_field' => 'value',
            ],
            [
                'key' => 'ref_bet',
                'value' => '20000',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'bonus ajak teman between bonus',
                'result_field' => 'value',
            ],
            [
                'key' => 'ref_gt',
                'value' => '50000',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'bonus ajak teman greater bonus',
                'result_field' => 'value',
            ],
            [
                'key' => 'ev_ref',
                'value' => 'ACTIVE',
                'source_type' => 'manual',
                'data_model_id' => null,
                'lookup_field' => null,
                'lookup_value' => null,
                'result_field' => null,
            ],
            [
                'key' => 'brt_min',
                'value' => '50000',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'bonus depo continue min amount',
                'result_field' => 'value',
            ],
            [
                'key' => 'brt_mul',
                'value' => '1',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'bonus depo continue multiplier',
                'result_field' => 'value',
            ],
            [
                'key' => 'brt_max',
                'value' => '50000',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'bonus depo continue max amount',
                'result_field' => 'value',
            ],
            [
                'key' => 'brt_cnt',
                'value' => '8',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'bonus depo continue count',
                'result_field' => 'value',
            ],
            [
                'key' => 'brt_nonbank',
                'value' => '0',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'bonus depo continue non bank include',
                'result_field' => 'value',
            ],
            [
                'key' => 'cb_day',
                'value' => '1',
                'description' => '1=Senin, 2=Selasa, 3=Rabu, 4=Kamis, 5=Jumat, 6=Sabtu, 7=Minggu',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'bonus cashback day',
                'result_field' => 'value',
            ],
            [
                'key' => 'cb_type',
                'value' => 'game',
                'description' => 'game=By Game Loss, total=By Total Loss',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'Weekly Bonus bonuscashback',
                'result_field' => 'value',
            ],
            [
                'key' => 'cb_arc_min',
                'value' => '0',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'Weekly Bonus cb_arcademinamount',
                'result_field' => 'value',
            ],
            [
                'key' => 'cb_arc_rate',
                'value' => '0',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'Weekly Bonus cb_arcaderate',
                'result_field' => 'value',
            ],
            [
                'key' => 'cb_dd_min',
                'value' => '0',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'Weekly Bonus cb_ddminamount',
                'result_field' => 'value',
            ],
            [
                'key' => 'cb_dd_rate',
                'value' => '5',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'Weekly Bonus cb_ddrate',
                'result_field' => 'value',
            ],
            [
                'key' => 'cb_lc_min',
                'value' => '0',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'Weekly Bonus cb_livecasinominamount',
                'result_field' => 'value',
            ],
            [
                'key' => 'cb_lc_rate',
                'value' => '0',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'Weekly Bonus cb_livecasinorate',
                'result_field' => 'value',
            ],
            [
                'key' => 'cb_sa_min',
                'value' => '0',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'Weekly Bonus cb_sabungayamminamount',
                'result_field' => 'value',
            ],
            [
                'key' => 'cb_sa_rate',
                'value' => '0',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'Weekly Bonus cb_sabungayamrate',
                'result_field' => 'value',
            ],
            [
                'key' => 'cb_slot_min',
                'value' => '0',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'Weekly Bonus cb_slotminamount',
                'result_field' => 'value',
            ],
            [
                'key' => 'cb_slot_rate',
                'value' => '0',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'Weekly Bonus cb_slotrate',
                'result_field' => 'value',
            ],
            [
                'key' => 'cb_spt_min',
                'value' => '0',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'Weekly Bonus cb_sportsminamount',
                'result_field' => 'value',
            ],
            [
                'key' => 'cb_spt_rate',
                'value' => '0',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'Weekly Bonus cb_sportsrate',
                'result_field' => 'value',
            ],
            [
                'key' => 'cb_esp_min',
                'value' => '0',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'Weekly Bonus cb_esportsminamount',
                'result_field' => 'value',
            ],
            [
                'key' => 'cb_esp_rate',
                'value' => '0',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'Weekly Bonus cb_esportsrate',
                'result_field' => 'value',
            ],
            [
                'key' => 'cb_tbl_min',
                'value' => '0',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'Weekly Bonus cb_tablegameminamount',
                'result_field' => 'value',
            ],
            [
                'key' => 'cb_tbl_rate',
                'value' => '0',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'Weekly Bonus cb_tablegamerate',
                'result_field' => 'value',
            ],
            [
                'key' => 'cb_tgk_min',
                'value' => '0',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'Weekly Bonus cb_tangkasminamount',
                'result_field' => 'value',
            ],
            [
                'key' => 'cb_tgk_rate',
                'value' => '0',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'Weekly Bonus cb_tangkasrate',
                'result_field' => 'value',
            ],
            [
                'key' => 'cb_tgl_min',
                'value' => '0',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'Weekly Bonus cb_togelminamount',
                'result_field' => 'value',
            ],
            [
                'key' => 'cb_tgl_rate',
                'value' => '0',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'Weekly Bonus cb_togelrate',
                'result_field' => 'value',
            ],
            [
                'key' => 'cb_tot_min',
                'value' => '0',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'Weekly Bonus cb_totalminamount',
                'result_field' => 'value',
            ],
            [
                'key' => 'cb_tot_rate',
                'value' => '0',
                'source_type' => 'datamodel_lookup',
                'data_model_id' => $settingsDataModelId,
                'lookup_field' => 'kode',
                'lookup_value' => 'Weekly Bonus cb_totalrate',
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
