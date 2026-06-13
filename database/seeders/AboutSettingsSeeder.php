<?php

namespace Database\Seeders;

use App\Models\HomepageSetting;
use Illuminate\Database\Seeder;

class AboutSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'about_headline'     => 'We build digital tools for the built world.',
            'about_story_link'   => '#',
            'about_beige_text_1' => 'Merisimo is a boutique web development agency specialising in digital products for the construction and real estate sector.',
            'about_beige_text_2' => 'Our flagship product is an interactive floor plan plugin that lets buyers browse buildings, select apartments by floor and status, and view pricing — embedded directly in your website.',
            'about_cta_title'    => 'Work with us',
            'about_cta_link_text' => 'Introduce yourself',
        ];

        foreach ($defaults as $key => $value) {
            HomepageSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
