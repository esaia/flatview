<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use IrepPlugin\FilamentIrep\Models\Project as IrepProject;

/**
 * A public showcase page for an interactive IREP project (/projects/{slug}).
 */
class DemoProject extends Model
{
    protected $fillable = [
        'project_id',
        'slug',
        'title',
        'tagline',
        'card_image',
        'hero_image',
        'hero_description',
        'plan_kicker',
        'plan_headline',
        'plan_headline_accent',
        'plan_intro',
        'units_kicker',
        'units_headline',
        'units_headline_accent',
        'units_intro',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function irepProject(): BelongsTo
    {
        return $this->belongsTo(IrepProject::class, 'project_id');
    }
}
