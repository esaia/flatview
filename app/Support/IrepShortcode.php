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
    public static function forProject(int|string $identifier): ?array
    {
        $with = ['meta', 'blocks', 'floors', 'flats.type', 'types', 'tooltips'];

        // Resolve by slug first; fall back to numeric id so existing
        // /project/{id} URLs keep working. The slug is compared as a string on
        // purpose: given an integer, MySQL casts the column to a number and
        // "2-view" would match the id 2.
        $project = Project::with($with)->where('slug', (string) $identifier)->first();

        if (! $project && is_numeric($identifier)) {
            $project = Project::with($with)->find($identifier);
        }

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
            'blocks' => $project->blocks,
            'floors' => $project->floors,
            'flats' => $project->flats,
            'types' => $project->types,
            'meta' => $meta,
            'actions' => $project->tooltips,
            'configs' => self::configs(),
        ];

        // Stored image URLs may be absolute (e.g. http://localhost:8000/storage/...)
        // captured from a different host/port. Normalize them to relative paths so
        // they resolve regardless of where the app is served.
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $json = preg_replace('#https?://[^/"\\\\]+/storage/#', '/storage/', $json);

        // Enrich after the round-trip, where models have become plain arrays.
        return self::withNumericFields(json_decode($json, true));
    }

    /**
     * The viewer reads `price_n` / `area_m2_n` — numeric siblings of the
     * decimal-string columns, a WordPress-era convention several components
     * still rely on. Nothing here produces them, so derive them once.
     */
    protected static function withNumericFields(array $data): array
    {
        $number = fn ($value) => is_numeric($value) ? (float) $value : null;

        $withTypeNumbers = function ($type) use ($number) {
            if (! is_array($type)) {
                return $type;
            }

            $type['area_m2_n'] = $number($type['area_m2'] ?? null);
            $type['rooms_count_n'] = $number($type['rooms_count'] ?? null);

            return $type;
        };

        $data['types'] = array_map($withTypeNumbers, (array) ($data['types'] ?? []));

        $data['flats'] = array_map(function ($flat) use ($number, $withTypeNumbers) {
            $flat['price_n'] = $number($flat['price'] ?? null);
            $flat['offer_price_n'] = $number($flat['offer_price'] ?? null);

            foreach (['type', 'flat_type'] as $key) {
                if (isset($flat[$key])) {
                    $flat[$key] = $withTypeNumbers($flat[$key]);
                }
            }

            return $flat;
        }, (array) ($data['flats'] ?? []));

        return $data;
    }

    /**
     * Table/list-view configuration from the IREP settings store, in the shape
     * the front-end expects. Columns fall back to a sensible default set so the
     * list view is usable before an admin configures any.
     */
    protected static function configs(): array
    {
        $tableFields = Setting::get('irep_table_fields', []);

        if (! is_array($tableFields) || $tableFields === [] || ! is_array(reset($tableFields))) {
            $tableFields = [
                ['field' => 'flat_number', 'header' => 'Unit', 'sortable' => true],
                ['field' => 'type.area_m2', 'header' => 'Area', 'sortable' => true],
                ['field' => 'type.rooms_count', 'header' => 'Rooms', 'sortable' => true],
                ['field' => 'price', 'header' => 'Price', 'sortable' => true],
            ];
        }

        $actionSvgs = Setting::get('irep_table_action_svgs', []);

        return [
            'flatFieldQueryParameter' => Setting::get('flatFieldQueryParameter', 'id') ?: 'id',
            'tableFields' => array_values($tableFields),
            'tableContactUrl' => Setting::get('irep_table_contact_url', '') ?: '',
            'hasTableOneColumn' => (bool) Setting::get('irep_table_one_column', false),
            'hasTableWholeRowClickable' => (bool) Setting::get('irep_table_whole_row_clickable', true),
            'tableActionSvgs' => is_array($actionSvgs)
                ? $actionSvgs + ['contactSvg' => '', 'downloadSvg' => '', 'magnifySvg' => '']
                : ['contactSvg' => '', 'downloadSvg' => '', 'magnifySvg' => ''],
        ];
    }
}
