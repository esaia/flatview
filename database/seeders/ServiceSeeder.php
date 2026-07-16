<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        if (Service::count() > 0) {
            return;
        }

        $services = [
            [
                'name' => 'Website development',
                'slug' => 'website-development',
                'description' => 'Custom websites for construction and real estate companies. Custom-built, Fast, SEO-ready, mobile-first — designed to convert visitors into clients.',
                'content_blocks' => [
                    [
                        'type' => 'rich_text',
                        'data' => [
                            'content' => '<p>We design and build marketing websites purpose-made for construction and real estate companies — fast, SEO-ready, and mobile-first from the first pixel.</p><p>Every site is <strong>custom-built</strong> around your projects and sales goals, not assembled from a generic template.</p>',
                        ],
                    ],
                    [
                        'type' => 'feature_list',
                        'data' => [
                            'heading' => "What's included",
                            'items' => [
                                ['title' => 'Custom design', 'detail' => 'No templates — every layout is designed around your brand and your projects.'],
                                ['title' => 'SEO-ready', 'detail' => 'Clean markup, fast load times, and structured content built to rank.'],
                                ['title' => 'Mobile-first', 'detail' => 'Most buyers browse on their phones — every page is designed there first.'],
                                ['title' => 'Built to convert', 'detail' => 'Clear calls to action guide visitors toward booking a viewing or making an inquiry.'],
                            ],
                        ],
                    ],
                    [
                        'type' => 'quote',
                        'data' => [
                            'quote' => 'Our new site paid for itself in the first month — inquiries doubled almost overnight.',
                            'attribution' => 'A construction-firm client',
                        ],
                    ],
                    [
                        'type' => 'stats',
                        'data' => [
                            'items' => [
                                ['value' => '3–6 wks', 'label' => 'Typical delivery time'],
                                ['value' => '98', 'label' => 'Average Lighthouse score'],
                                ['value' => '2×', 'label' => 'Median inquiry increase'],
                            ],
                        ],
                    ],
                ],
                'sort_order' => 1,
            ],
            [
                'name' => 'Building module',
                'slug' => 'building-module',
                'description' => 'Interactive building visualizer. Browse floors, select apartments, view availability and pricing — with immersive 360° floor views, embedded directly on your website with no third-party platform needed.',
                'content_blocks' => [
                    [
                        'type' => 'rich_text',
                        'data' => [
                            'content' => '<p>Our flagship product: an interactive building visualizer that lets buyers browse floors, select apartments, and check availability and pricing in real time.</p><p>Immersive 360° floor views embed directly on your existing website — no third-party platform, no separate login, no extra maintenance burden.</p>',
                        ],
                    ],
                    [
                        'type' => 'image_text',
                        'data' => [
                            'image' => '',
                            'heading' => 'Browse floor by floor',
                            'text' => 'Buyers navigate the building visually, floor by floor, unit by unit — with immersive 360° panoramic views of each floor plan.',
                            'image_position' => 'left',
                        ],
                    ],
                    [
                        'type' => 'image_text',
                        'data' => [
                            'image' => '',
                            'heading' => 'Live availability & pricing',
                            'text' => 'Statuses and prices update the moment your team does — no outdated PDFs, no confirmation calls.',
                            'image_position' => 'right',
                        ],
                    ],
                    [
                        'type' => 'feature_list',
                        'data' => [
                            'heading' => "What's included",
                            'items' => [
                                ['title' => 'Floor-by-floor browsing', 'detail' => 'Buyers navigate the building visually, floor by floor, unit by unit.'],
                                ['title' => '360° floor views', 'detail' => 'Immersive panoramic views of each floor plan, embedded directly on your site.'],
                                ['title' => 'Live availability & pricing', 'detail' => 'Statuses and prices update the moment your team does — no outdated PDFs.'],
                                ['title' => 'No third-party platform', 'detail' => 'Runs on your own domain, fully under your brand.'],
                            ],
                        ],
                    ],
                ],
                'sort_order' => 2,
            ],
            [
                'name' => 'Maintenance & support',
                'slug' => 'maintenance-support',
                'description' => 'Ongoing updates, hosting management, and technical support so your digital presence stays fast, secure, and reliable long after launch.',
                'content_blocks' => [
                    [
                        'type' => 'rich_text',
                        'data' => [
                            'content' => '<p>Launch day is the start, not the finish. We keep your site and building module fast, secure, and up to date long after go-live.</p><ul><li>Hosting management and uptime monitoring</li><li>Ongoing content and feature updates</li><li>A direct line to the team that built it</li></ul><p>Retainer options are available from day one, so there\'s always a clear point of contact when something needs attention.</p>',
                        ],
                    ],
                    [
                        'type' => 'stats',
                        'data' => [
                            'items' => [
                                ['value' => '99.9%', 'label' => 'Uptime target'],
                                ['value' => '<24h', 'label' => 'Typical response time'],
                                ['value' => '0', 'label' => 'Ticket queues'],
                            ],
                        ],
                    ],
                    [
                        'type' => 'feature_list',
                        'data' => [
                            'heading' => "What's included",
                            'items' => [
                                ['title' => 'Hosting management', 'detail' => 'We monitor uptime, performance, and security so you don\'t have to.'],
                                ['title' => 'Ongoing updates', 'detail' => 'Content changes, new listings, and feature additions handled on request.'],
                                ['title' => 'Technical support', 'detail' => 'A direct line to the team that built your site — no ticket queues.'],
                                ['title' => 'Proactive monitoring', 'detail' => 'Issues get caught and fixed before your visitors notice them.'],
                            ],
                        ],
                    ],
                    [
                        'type' => 'quote',
                        'data' => [
                            'quote' => 'Whenever something comes up, we get a real answer within hours, not a support ticket number.',
                            'attribution' => 'A retainer client',
                        ],
                    ],
                ],
                'sort_order' => 3,
            ],
        ];

        foreach ($services as $service) {
            Service::create($service);
        }
    }
}
