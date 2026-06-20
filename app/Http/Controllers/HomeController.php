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
            'demoData' => $demoProjectId ? IrepShortcode::forProject($demoProjectId) : null,
        ]);
    }
}
