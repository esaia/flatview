<?php

namespace App\Http\Controllers;

use App\Models\AboutGalleryImage;
use App\Models\AboutStat;
use App\Models\HomepageSetting;
use Inertia\Inertia;

class AboutController extends Controller
{
    public function index()
    {
        $settings = HomepageSetting::whereIn('key', [
            'about_headline',
            'about_story_link',
            'about_beige_text_1',
            'about_beige_text_2',
            'about_cta_title',
            'about_cta_link_text',
        ])->pluck('value', 'key');

        $gallery = AboutGalleryImage::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $stats = AboutStat::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('About', compact('settings', 'gallery', 'stats'));
    }
}
