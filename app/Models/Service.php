<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'hero_image',
        'hover_image',
        'cta_background',
        'content_blocks',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'content_blocks' => 'array',
        'is_active' => 'boolean',
    ];

    // No slug-based route key: a slug may be a full external URL, which would
    // break admin URLs. The public route resolves the slug manually instead.
}
