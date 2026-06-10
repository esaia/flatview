<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            [
                'key'   => 'irep_table_fields',
                'value' => json_encode(['flat_number', 'floor', 'area', 'price', 'status']),
                'type'  => 'json',
            ],
            [
                'key'   => 'irep_table_contact_url',
                'value' => '',
                'type'  => 'string',
            ],
            [
                'key'   => 'flatFieldQueryParameter',
                'value' => 'flat',
                'type'  => 'string',
            ],
            [
                'key'   => 'irep_table_one_column',
                'value' => '0',
                'type'  => 'boolean',
            ],
            [
                'key'   => 'irep_table_whole_row_clickable',
                'value' => '0',
                'type'  => 'boolean',
            ],
            [
                'key'   => 'irep_custom_status_types',
                'value' => json_encode([]),
                'type'  => 'json',
            ],
            [
                'key'   => 'irep_table_action_svgs',
                'value' => json_encode(['contactSvg' => '', 'downloadSvg' => '', 'magnifySvg' => '']),
                'type'  => 'json',
            ],
        ];

        foreach ($defaults as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
