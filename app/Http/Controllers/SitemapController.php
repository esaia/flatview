<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Public marketing pages, keyed by route name with a crawl priority and
     * change frequency. Auth/admin/demo routes are intentionally excluded.
     *
     * @var array<string, array{priority: string, changefreq: string}>
     */
    private array $pages = [
        'home'     => ['priority' => '1.0', 'changefreq' => 'weekly'],
        'work'     => ['priority' => '0.8', 'changefreq' => 'weekly'],
        'services' => ['priority' => '0.8', 'changefreq' => 'monthly'],
        'about'    => ['priority' => '0.6', 'changefreq' => 'monthly'],
        'contact'  => ['priority' => '0.5', 'changefreq' => 'yearly'],
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

        foreach (Service::where('is_active', true)->get(['slug', 'updated_at']) as $service) {
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

        $xml = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
        {$urls}</urlset>
        XML;

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
