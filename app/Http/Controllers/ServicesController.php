<?php

namespace App\Http\Controllers;

use App\Models\DemoProject;
use App\Models\HomepageSetting;
use App\Models\Service;
use App\Support\RichText;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class ServicesController extends Controller
{
    public function index()
    {
        $stored = HomepageSetting::where('key', 'like', 'services_%')
            ->pluck('value', 'key')
            ->toArray();

        $get = fn (string $key, $default = '') => filled($stored[$key] ?? null) ? $stored[$key] : $default;
        $getJson = fn (string $key, array $default = []) => $this->json($stored[$key] ?? null, $default);

        $settings = [
            'kicker' => $get('services_kicker', 'Our Services'),
            'headline' => $get('services_headline', 'What we do'),
            'intro' => $get('services_intro', 'We build digital products for the construction and real estate sector — from marketing websites to interactive tools that help sell properties faster.'),

            'galleryKicker' => $get('services_gallery_kicker', 'Selected Work'),

            'blocks' => Service::where('is_active', true)
                ->orderBy('sort_order')
                ->get(['name', 'slug', 'description', 'hover_image'])
                ->map(fn (Service $service) => [
                    'name' => $service->name,
                    'slug' => $service->slug,
                    'description' => $service->description,
                    'hoverImage' => DemoProjectController::imageUrl($service->hover_image),
                ])
                ->all(),

            'projectsKicker' => $get('services_projects_kicker', 'Live demos'),
            'projectsHeadline' => $get('services_projects_headline', 'See it working'),
            'projectsHeadlineAccent' => $get('services_projects_headline_accent', 'on a real project'),
            'projectsIntro' => $get('services_projects_intro', 'Open a demo and use it exactly as your buyers would — click a unit on the site plan, filter the list, check availability.'),

            'mattersKicker' => $get('services_matters_kicker', 'Why it matters'),
            'mattersHeadline' => $get('services_matters_headline', "Your clients look\nfor answers"),
            'mattersHeadlineAccent' => $get('services_matters_headline_accent', 'before they call'),
            'mattersIntro' => $get('services_matters_intro', 'Most buying decisions start online. Buyers browse available units, check sizes and prices — before contacting the developer. The question is: does your website make this possible?'),
            'matters' => $getJson('services_matters', [
                ['title' => 'First impression', 'description' => 'A static image gallery and a PDF table are not enough. Buyers expect a modern experience — the ability to explore the offer on their own, in real time.'],
                ['title' => 'Time is money', 'description' => 'Every inquiry about unit availability that you handle manually is time lost. Automated statuses and unit pages remove repetitive questions and shorten the sales process.'],
                ['title' => 'The competition never sleeps', 'description' => 'Large developers invest in advanced sales platforms. Flatview gives you the same capabilities — without a corporate-level budget.'],
            ]),

            'featuresKicker' => $get('services_features_kicker', 'The product, shown'),
            'featuresHeadline' => $get('services_features_headline', "What used to take a showroom\nnow takes a scroll."),
            'features' => $getJson('services_features', [
                ['title' => 'Detailed floor plan for each unit', 'description' => 'Click any unit to see the full picture: floor plan, size, orientation, and price. The whole overview, before construction begins.', 'image' => ''],
                ['title' => 'Real-time availability', 'description' => 'Statuses update the moment your team does. No outdated PDFs, no confirmation calls — just information that is always accurate.', 'image' => ''],
                ['title' => 'Lead capture & interest registration', 'description' => 'Prospects register interest in a single unit or the whole project. Every inquiry runs through a predefined workflow.', 'image' => ''],
                ['title' => 'Smart filtering', 'description' => 'Filter by floor, rooms, size, price, or availability. Instant results, no reloads — the experience your buyers already expect.', 'image' => ''],
            ]),

            'processKicker' => $get('services_process_kicker', 'How we work'),
            'processHeadline' => $get('services_process_headline', 'A clear path from first call to launch day.'),
            'processIntro' => $get('services_process_intro', 'Four steps, no surprises — you always know where your project stands.'),
            'process' => $getJson('services_process', [
                ['title' => 'Discovery', 'detail' => 'We learn your projects, units, and sales goals — then map the structure your buyers will navigate.'],
                ['title' => 'Design', 'detail' => 'Layouts and interactions tailored to your brand, built mobile-first and ready to convert.'],
                ['title' => 'Build', 'detail' => 'Development of the website and interactive building module, wired to live availability and pricing.'],
                ['title' => 'Launch & care', 'detail' => 'We ship, host, and keep everything fast and secure — with support long after go-live.'],
            ]),

            'ctaKicker' => $get('services_cta_kicker', "Let's talk"),
            'ctaHeadline' => $get('services_cta_headline', "Let's make real estate\ndigitally perfect."),
            'ctaButtonText' => $get('services_cta_button_text', 'Schedule a meeting'),
            'ctaButtonLink' => $get('services_cta_button_link', '/contact'),
        ];

        $gallery = array_map(
            fn (string $path) => ['image' => $path],
            $this->json($stored['services_gallery'] ?? null, []),
        );

        $projects = self::demoProjectCards(array_map(
            'intval',
            $this->json($stored['services_projects_selected'] ?? null, []),
        ));

        $faq = $this->faq();

        return Inertia::render('Services', compact('settings', 'gallery', 'projects', 'faq'));
    }

    public function show(string $slug)
    {
        $service = Service::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $stored = HomepageSetting::whereIn('key', ['services_cta_kicker', 'services_cta_headline', 'services_cta_button_text', 'services_cta_button_link'])
            ->pluck('value', 'key')
            ->toArray();

        $get = fn (string $key, $default = '') => filled($stored[$key] ?? null) ? $stored[$key] : $default;

        $cta = [
            'kicker' => $get('services_cta_kicker', "Let's talk"),
            'headline' => $get('services_cta_headline', "Let's make real estate\ndigitally perfect."),
            'buttonText' => $get('services_cta_button_text', 'Schedule a meeting'),
            'buttonLink' => $get('services_cta_button_link', '/contact'),
        ];

        // The image+text block's copy became a rich editor; blocks saved before
        // that still hold plain text, which the page renders as HTML.
        $service->content_blocks = collect($service->content_blocks ?? [])
            ->map(function (array $block): array {
                if (($block['type'] ?? null) === 'image_text') {
                    $block['data']['text'] = RichText::fromPlain($block['data']['text'] ?? null);
                }

                // The block stores only which projects were picked; the cards
                // themselves are built here, as on the services overview.
                if (($block['type'] ?? null) === 'demo_projects') {
                    $block['data']['projects'] = self::demoProjectCards(array_map(
                        'intval',
                        (array) ($block['data']['project_ids'] ?? []),
                    ));
                }

                return $block;
            })
            ->all();

        $otherServices = Service::where('is_active', true)
            ->where('id', '!=', $service->id)
            ->orderBy('sort_order')
            ->get(['name', 'slug', 'description']);

        return Inertia::render('ServiceShow', [
            'service' => $service,
            'cta' => $cta,
            'otherServices' => $otherServices,
        ]);
    }

    /**
     * Demo project cards for the "live demos" section, wherever it is used —
     * the services overview and the demo-projects content block both render the
     * same card. Each links to its own /projects/{slug} showcase page.
     *
     * `flats_count` comes from the linked IREP project so a card can state the
     * real unit count without loading the whole project tree. An explicit
     * selection wins, in the order it was picked; with none picked the section
     * falls back to every active project.
     *
     * @param  array<int, int>  $selected
     * @return Collection<int, array<string, mixed>>
     */
    protected static function demoProjectCards(array $selected = [])
    {
        $selected = array_values(array_filter($selected));

        return DemoProject::where('is_active', true)
            ->when($selected, fn ($query) => $query->whereIn('id', $selected))
            ->with(['irepProject' => fn ($query) => $query->withCount('flats')])
            ->orderBy('sort_order')
            ->get()
            ->sortBy(fn (DemoProject $project) => $selected
                ? array_search($project->id, $selected, true)
                : $project->sort_order)
            ->map(fn (DemoProject $project) => [
                'title' => $project->title,
                'slug' => $project->slug,
                'tagline' => $project->tagline,
                // Fall back to the interactive project's own render, so a card
                // still shows the development when no artwork was uploaded.
                'image' => DemoProjectController::imageUrl($project->card_image ?: $project->hero_image)
                    ?? self::irepProjectImage($project),
                'unitCount' => $project->irepProject?->flats_count,
            ])
            ->values();
    }

    /**
     * The main render of the IREP project behind a demo page, if it has one.
     * Stored image urls may be absolute and captured from another host, so they
     * are normalized to the local /storage path.
     */
    protected static function irepProjectImage(DemoProject $project): ?string
    {
        $image = $project->irepProject?->project_image;
        $url = is_array($image) ? ($image[0]['url'] ?? null) : null;

        if (! is_string($url) || $url === '') {
            return null;
        }

        return preg_replace('#^https?://[^/]+/storage/#', '/storage/', $url);
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
