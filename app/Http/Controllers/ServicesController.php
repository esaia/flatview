<?php

namespace App\Http\Controllers;

use App\Models\AboutGalleryImage;
use Inertia\Inertia;

class ServicesController extends Controller
{
    public function index()
    {
        $gallery = AboutGalleryImage::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Services', compact('gallery'));
    }
}
