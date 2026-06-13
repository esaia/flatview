<?php

namespace Database\Seeders;

use App\Models\HomepageSetting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        HomepageSetting::updateOrCreate(
            ['key' => 'badge'],
            ['value' => 'Web Development Agency']
        );

        HomepageSetting::updateOrCreate(
            ['key' => 'headline'],
            ['value' => "Digital\nPresence\nFor Builders."]
        );

        HomepageSetting::updateOrCreate(
            ['key' => 'subtitle'],
            ['value' => 'Websites and interactive floor plan tools for construction and real estate companies.']
        );
    }
}
