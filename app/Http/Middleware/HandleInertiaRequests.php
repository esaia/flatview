<?php

namespace App\Http\Middleware;

use App\Models\HomepageSetting;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'success' => session('success'),
                'error'   => session('error'),
            ],
            'menuItems' => MenuItem::where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'label', 'href', 'external', 'image']),
            'siteSocials' => $this->siteSocials(),
        ];
    }

    /**
     * The site-wide social links (label + section heading) shown in the
     * floating menu's bottom bar, sourced from the Contact page settings.
     *
     * @return array{label: string, links: array<int, array{label: string, url: string}>}
     */
    protected function siteSocials(): array
    {
        $stored = HomepageSetting::whereIn('key', ['contact_social_label', 'contact_socials'])
            ->pluck('value', 'key')
            ->toArray();

        $links = json_decode($stored['contact_socials'] ?? '[]', true);

        return [
            'label' => filled($stored['contact_social_label'] ?? null) ? $stored['contact_social_label'] : 'Follow us',
            'links' => is_array($links) ? array_values(array_filter(
                $links,
                fn ($link) => is_array($link) && filled($link['label'] ?? null),
            )) : [],
        ];
    }
}
