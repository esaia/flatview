<?php

namespace App\Http\Controllers;

use App\Models\HomepageSetting;
use App\Models\HomepageSlide;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        $slides = HomepageSlide::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $settings = HomepageSetting::pluck('value', 'key');

        return Inertia::render('Home', [
            'slides' => $slides,
            'settings' => $settings,
        ]);
    }
}
