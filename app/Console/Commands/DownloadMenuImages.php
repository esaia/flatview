<?php

namespace App\Console\Commands;

use App\Models\MenuItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class DownloadMenuImages extends Command
{
    protected $signature = 'menu:download-images';

    protected $description = 'Download Unsplash images for navigation menu items';

    public function handle(): void
    {
        $items = [
            'Home'     => ['url' => 'https://images.unsplash.com/photo-1486325212027-8081e485255e?w=400&q=80', 'file' => 'menu/home.jpg'],
            'Work'     => ['url' => 'https://images.unsplash.com/photo-1503387762-592deb58ef4e?w=400&q=80', 'file' => 'menu/work.jpg'],
            'Services' => ['url' => 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=400&q=80', 'file' => 'menu/services.jpg'],
            'About'    => ['url' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=400&q=80', 'file' => 'menu/about.jpg'],
            'Contact'  => ['url' => 'https://images.unsplash.com/photo-1423666639041-f56000c27a9a?w=400&q=80', 'file' => 'menu/contact.jpg'],
        ];

        foreach ($items as $label => $config) {
            $this->info("Downloading {$label}...");

            $response = Http::get($config['url']);

            if (! $response->successful()) {
                $this->error("Failed to download image for {$label} (HTTP {$response->status()})");
                continue;
            }

            Storage::disk('public')->put($config['file'], $response->body());

            MenuItem::where('label', $label)->update(['image' => $config['file']]);

            $this->line("  Saved {$config['file']} and updated DB.");
        }

        $this->info('Done.');
    }
}
