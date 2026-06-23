<?php

namespace Database\Seeders;

use App\Models\AboutGalleryImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class AboutGalleryImageSeeder extends Seeder
{
    public function run(): void
    {
        if (AboutGalleryImage::count() > 0) {
            return;
        }

        $images = [
            ['url' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=800&q=80', 'filename' => 'about-gallery-1.jpg'],
            ['url' => 'https://images.unsplash.com/photo-1497366811353-6870744d04b2?w=600&q=80', 'filename' => 'about-gallery-2.jpg'],
            ['url' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=600&q=80', 'filename' => 'about-gallery-3.jpg'],
            ['url' => 'https://images.unsplash.com/photo-1503387762-592deb58ef4e?w=800&q=80',  'filename' => 'about-gallery-4.jpg'],
            ['url' => 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=600&q=80',  'filename' => 'about-gallery-5.jpg'],
            ['url' => 'https://images.unsplash.com/photo-1486325212027-8081e485255e?w=600&q=80', 'filename' => 'about-gallery-6.jpg'],
        ];

        foreach ($images as $index => $item) {
            $relativePath = 'about-gallery/' . $item['filename'];

            if (!Storage::disk('public')->exists($relativePath)) {
                $response = Http::timeout(30)->get($item['url']);

                if ($response->successful()) {
                    Storage::disk('public')->put($relativePath, $response->body());
                }
            }

            AboutGalleryImage::create([
                'image'      => $relativePath,
                'sort_order' => $index + 1,
                'is_active'  => true,
            ]);
        }
    }
}
