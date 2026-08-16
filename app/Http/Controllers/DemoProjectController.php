<?php

namespace App\Http\Controllers;

use App\Models\DemoProject;
use App\Models\HomepageSetting;
use App\Support\IrepShortcode;
use Inertia\Inertia;

class DemoProjectController extends Controller
{
    public function show(string $slug)
    {
        $demo = DemoProject::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $stored = HomepageSetting::whereIn('key', ['services_cta_kicker', 'services_cta_headline', 'services_cta_button_text', 'services_cta_button_link'])
            ->pluck('value', 'key')
            ->toArray();

        $get = fn (string $key, $default = '') => filled($stored[$key] ?? null) ? $stored[$key] : $default;

        return Inertia::render('DemoProjectShow', [
            'project' => [
                'title' => $demo->title,
                'slug' => $demo->slug,
                'tagline' => $demo->tagline,
                'heroImage' => self::imageUrl($demo->hero_image),
                'heroDescription' => $demo->hero_description,
                'planKicker' => $demo->plan_kicker ?: 'Interactive presentation',
                'planHeadline' => $demo->plan_headline ?: 'Click on a unit and',
                'planHeadlineAccent' => $demo->plan_headline_accent ?: 'check the details',
                'planIntro' => $demo->plan_intro,
                'unitsKicker' => $demo->units_kicker ?: 'Available units',
                'unitsHeadline' => $demo->units_headline ?: 'Find a unit that',
                'unitsHeadlineAccent' => $demo->units_headline_accent ?: 'matches your requirements',
                'unitsIntro' => $demo->units_intro,
            ],
            // Null when the linked IREP project was deleted — the page then
            // renders its copy without the viewer instead of erroring.
            'projectData' => $demo->project_id ? IrepShortcode::forProject($demo->project_id) : null,
            'cta' => [
                'kicker' => $get('services_cta_kicker', "Let's talk"),
                'headline' => $get('services_cta_headline', "Let's make real estate\ndigitally perfect."),
                'buttonText' => $get('services_cta_button_text', 'Schedule a meeting'),
                'buttonLink' => $get('services_cta_button_link', '/contact'),
            ],
        ]);
    }

    /** Resolve a stored public-disk path to a servable URL. */
    public static function imageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return str_starts_with($path, 'http') ? $path : '/storage/'.ltrim($path, '/');
    }
}
