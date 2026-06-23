<?php

namespace App\Http\Controllers;

use App\Models\HomepageSetting;
use Inertia\Inertia;

class WorkController extends Controller
{
    public function index()
    {
        $stored = HomepageSetting::where('key', 'like', 'work_%')
            ->pluck('value', 'key')
            ->toArray();

        $get = fn (string $key, $default = '') => filled($stored[$key] ?? null) ? $stored[$key] : $default;

        $settings = [
            'kicker' => $get('work_kicker', 'Portfolio'),
            'headline' => $get('work_headline', 'Selected Work'),
            'intro' => $get('work_intro', 'Real estate platforms, interactive floor-plan tooling and the studio work behind them.'),
        ];

        $projects = $this->json($stored['work_projects'] ?? null, [
            ['name' => 'Tbilisi Towers',     'desc' => 'Real estate website'],
            ['name' => 'SkyView Residences', 'desc' => 'Floor plan plugin'],
            ['name' => 'BuildCore',          'desc' => 'Construction company'],
            ['name' => 'Axis Group',         'desc' => 'Corporate website'],
            ['name' => 'Nova Properties',    'desc' => 'Real estate portal'],
            ['name' => 'Archi Studio',       'desc' => 'Architecture firm'],
        ]);

        $projects = array_map(fn (array $p) => [
            'name' => $p['name'] ?? '',
            'desc' => $p['desc'] ?? '',
            'link' => filled($p['link'] ?? null) ? $p['link'] : null,
            'image' => filled($p['image'] ?? null) ? '/storage/'.ltrim($p['image'], '/') : null,
        ], $projects);

        return Inertia::render('Work', compact('settings', 'projects'));
    }

    /**
     * Decode a JSON setting value, falling back to a default when empty/invalid.
     */
    protected function json(?string $value, array $default): array
    {
        if (blank($value)) {
            return $default;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) && count($decoded) ? $decoded : $default;
    }
}
