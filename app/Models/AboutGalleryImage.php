<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutGalleryImage extends Model
{
    protected $fillable = ['image', 'sort_order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
