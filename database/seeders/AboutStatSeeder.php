<?php

namespace Database\Seeders;

use App\Models\AboutStat;
use Illuminate\Database\Seeder;

class AboutStatSeeder extends Seeder
{
    public function run(): void
    {
        if (AboutStat::count() > 0) {
            return;
        }

        $stats = [
            ['value' => '2024',    'label' => 'The year we founded the company',          'sort_order' => 1],
            ['value' => 'GE + EU', 'label' => 'Markets we serve',                         'sort_order' => 2],
            ['value' => '10+',     'label' => 'Projects delivered',                        'sort_order' => 3],
            ['value' => '1',       'label' => 'Unique floor plan plugin for construction', 'sort_order' => 4],
        ];

        foreach ($stats as $stat) {
            AboutStat::create($stat);
        }
    }
}
