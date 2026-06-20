<?php

namespace App\Support;

use IrepPlugin\FilamentIrep\Models\Project;
use IrepPlugin\FilamentIrep\Models\Setting;

class IrepShortcode
{
    /**
     * Build the IREP shortcode payload for a project, with image URLs
     * normalized to relative paths so they resolve on any host/port.
     *
     * Returns null when the project does not exist.
     */
    public static function forProject(int|string $projectId): ?array
    {
        $project = Project::with(['meta', 'blocks', 'floors', 'flats.type', 'types', 'tooltips'])
            ->find($projectId);

        if (! $project) {
            return null;
        }

        $projectData = $project->toArray();
        $projectData['360images'] = $projectData['images_360'] ?? [];
        unset($projectData['images_360']);

        $customTypesSetting = Setting::where('key', 'irep_custom_status_types')->first();
        $customTypes = $customTypesSetting ? json_decode($customTypesSetting->value, true) : [];
        $meta = $project->meta->toArray();
        $meta[] = ['meta_key' => 'custom_types', 'meta_value' => is_array($customTypes) ? $customTypes : []];

        $data = [
            'project' => $projectData,
            'blocks'  => $project->blocks,
            'floors'  => $project->floors,
            'flats'   => $project->flats,
            'types'   => $project->types,
            'meta'    => $meta,
            'actions' => $project->tooltips,
        ];

        // Stored image URLs may be absolute (e.g. http://localhost:8000/storage/...)
        // captured from a different host/port. Normalize them to relative paths so
        // they resolve regardless of where the app is served.
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $json = preg_replace('#https?://[^/"\\\\]+/storage/#', '/storage/', $json);

        return json_decode($json, true);
    }
}
