<?php

namespace App\Http\Controllers;

use App\Models\HomepageSetting;
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

            'blocks' => $getJson('services_blocks', [
                ['name' => 'Website development', 'description' => 'Custom websites for construction and real estate companies. WordPress or custom-built. Fast, SEO-ready, mobile-first — designed to convert visitors into clients.'],
                ['name' => 'Building module', 'description' => 'Interactive building visualizer. Browse floors, select apartments, view availability and pricing — with immersive 360° floor views, embedded directly on your website with no third-party platform needed.'],
                ['name' => 'Maintenance & support', 'description' => 'Ongoing updates, hosting management, and technical support so your digital presence stays fast, secure, and reliable long after launch.'],
            ]),

            'valuepropsKicker' => $get('services_valueprops_kicker', 'Why Flatview?'),
            'valueprops' => $getJson('services_valueprops', [
                ['label' => 'Niche focused', 'detail' => 'We work exclusively in construction and real estate.'],
                ['label' => 'Fast delivery', 'detail' => 'Typical project turnaround: 3–6 weeks.'],
                ['label' => 'EU-ready', 'detail' => 'GDPR compliant, multi-language, EU-hosted.'],
                ['label' => 'Ongoing support', 'detail' => 'Retainer options from day one.'],
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

        return Inertia::render('Services', compact('settings', 'gallery'));
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
