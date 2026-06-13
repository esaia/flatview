<?php

namespace App\Console\Commands;

use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DownloadProjectImages extends Command
{
    protected $signature = 'projects:download-images';

    protected $description = 'Download external image URLs and store them locally on the public disk';

    public function handle(): int
    {
        $projects = Project::whereNotNull('image')
            ->where('image', 'like', 'http%')
            ->get();

        if ($projects->isEmpty()) {
            $this->info('No projects with external image URLs found.');
            return self::SUCCESS;
        }

        $this->info("Found {$projects->count()} project(s) with external images.");

        foreach ($projects as $project) {
            $slug = Str::slug($project->title) ?: $project->id;
            $filename = "projects/{$slug}.jpg";

            $this->line("  Downloading: {$project->title}");

            try {
                $response = Http::timeout(30)->get($project->image);

                if (!$response->successful()) {
                    $this->warn("  Failed ({$response->status()}): {$project->image}");
                    continue;
                }

                Storage::disk('public')->put($filename, $response->body());

                $project->update(['image' => $filename]);

                $this->line("  Saved to: {$filename}");
            } catch (\Exception $e) {
                $this->error("  Error for {$project->title}: {$e->getMessage()}");
            }
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
