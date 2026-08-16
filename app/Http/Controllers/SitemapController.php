<?php

namespace App\Http\Controllers;

use App\Models\DemoProject;
use App\Models\Service;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Fixed public pages, keyed by route name with a crawl priority and change
     * frequency. Service pages and demo project pages are appended from the
     * database below; auth, admin and the bare project viewer are excluded —
     * the demo project page is the canonical URL for a project.
     *
     * @var array<string, array{priority: string, changefreq: string}>
     */
    private array $pages = [
        'home' => ['priority' => '1.0', 'changefreq' => 'weekly'],
        'work' => ['priority' => '0.8', 'changefreq' => 'weekly'],
        'services' => ['priority' => '0.8', 'changefreq' => 'monthly'],
        'about' => ['priority' => '0.6', 'changefreq' => 'monthly'],
        'contact' => ['priority' => '0.5', 'changefreq' => 'yearly'],
    ];

    public function index(): Response
    {
        $lastmod = now()->toAtomString();

        $urls = '';
        foreach ($this->pages as $name => $meta) {
            $loc = htmlspecialchars(route($name), ENT_XML1);
            $urls .= <<<XML
                <url>
                    <loc>{$loc}</loc>
                    <lastmod>{$lastmod}</lastmod>
                    <changefreq>{$meta['changefreq']}</changefreq>
                    <priority>{$meta['priority']}</priority>
                </url>

            XML;
        }

        // A service "slug" may be a full external URL (e.g. the plugin's page
        // on wordpress.org). Those cards link straight out, so there is no page
        // of ours to list.
        $services = Service::where('is_active', true)
            ->get(['slug', 'updated_at'])
            ->reject(fn (Service $service) => str_starts_with((string) $service->slug, 'http'));

        foreach ($services as $service) {
            $loc = htmlspecialchars(route('services.show', $service->slug), ENT_XML1);
            $serviceLastmod = $service->updated_at?->toAtomString() ?? $lastmod;
            $urls .= <<<XML
                <url>
                    <loc>{$loc}</loc>
                    <lastmod>{$serviceLastmod}</lastmod>
                    <changefreq>monthly</changefreq>
                    <priority>0.7</priority>
                </url>

            XML;
        }

        foreach (DemoProject::where('is_active', true)->get(['slug', 'updated_at']) as $project) {
            $loc = htmlspecialchars(route('demo-project.show', $project->slug), ENT_XML1);
            $projectLastmod = $project->updated_at?->toAtomString() ?? $lastmod;
            $urls .= <<<XML
                <url>
                    <loc>{$loc}</loc>
                    <lastmod>{$projectLastmod}</lastmod>
                    <changefreq>weekly</changefreq>
                    <priority>0.7</priority>
                </url>

            XML;
        }

        $xml = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
        {$urls}</urlset>
        XML;

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
