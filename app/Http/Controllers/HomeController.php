<?php

namespace App\Http\Controllers;

use App\Models\HomepageSetting;
use App\Support\IrepShortcode;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        $settings = HomepageSetting::pluck('value', 'key');

        $demoProjectId = $settings['demo_project_id'] ?? null;

        return Inertia::render('Home', [
            'settings' => $settings,
            'demoProjectId' => $demoProjectId,
            // Deferred: the 360 demo payload is large (hundreds of flats/floors +
            // 360° SVGs). Keeping it out of the initial HTML keeps the document
            // small enough for link-preview crawlers (Twitter/X caps fetch size)
            // and lets the page paint first. Inertia auto-fetches it after load.
            'demoData' => $demoProjectId
                ? Inertia::defer(fn () => IrepShortcode::forProject($demoProjectId))
                : null,
        ]);
    }
}
