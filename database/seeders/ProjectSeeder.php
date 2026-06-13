<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $slides = [
            [
                'slug'             => 'tbilisi-towers',
                'title'            => 'Tbilisi Towers',
                'category'         => 'Real estate website',
                'year'             => '2024',
                'image'            => 'https://images.unsplash.com/photo-1486325212027-8081e485255e?w=1200&q=80',
                'background_color' => '#1a1a2e',
                'is_active'        => true,
                'sort_order'       => 1,
            ],
            [
                'slug'             => 'skyview-residences',
                'title'            => 'SkyView Residences',
                'category'         => 'Floor plan plugin',
                'year'             => '2024',
                'image'            => 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=1200&q=80',
                'background_color' => '#2d1b1b',
                'is_active'        => true,
                'sort_order'       => 2,
            ],
            [
                'slug'             => 'buildcore',
                'title'            => 'BuildCore',
                'category'         => 'Construction company',
                'year'             => '2024',
                'image'            => 'https://images.unsplash.com/photo-1503387762-592deb58ef4e?w=1200&q=80',
                'background_color' => '#1b2d1b',
                'is_active'        => true,
                'sort_order'       => 3,
            ],
            [
                'slug'             => 'axis-group',
                'title'            => 'Axis Group',
                'category'         => 'Corporate website',
                'year'             => '2023',
                'image'            => 'https://images.unsplash.com/photo-1460574283810-2aab119d8511?w=1200&q=80',
                'background_color' => '#1a1a1a',
                'is_active'        => true,
                'sort_order'       => 4,
            ],
        ];

        foreach ($slides as $slide) {
            Project::updateOrCreate(['slug' => $slide['slug']], $slide);
        }
    }
}
