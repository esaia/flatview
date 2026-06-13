<?php

namespace App\Http\Controllers;

use App\Models\HomepageSetting;
use App\Models\Project;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        $projects = Project::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $settings = HomepageSetting::pluck('value', 'key');

        return Inertia::render('Home', [
            'projects' => $projects,
            'settings' => $settings,
        ]);
    }
}
