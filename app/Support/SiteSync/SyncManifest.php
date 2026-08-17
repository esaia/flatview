<?php

namespace App\Support\SiteSync;

use Illuminate\Support\Facades\Storage;

/**
 * Describes what a site sync archive can contain: which database tables belong
 * to which content group, and which public-disk folders hold their uploads.
 *
 * Adding a new admin section? Register its table and upload directory here, or
 * its data will not travel between environments.
 */
class SyncManifest
{
    /** Bumped when the archive layout changes in a way older importers cannot read. */
    public const FORMAT_VERSION = 1;

    /** Parents before children, so inserts never violate a foreign key. */
    public const TABLE_ORDER = [
        'users',
        'projects',
        'blocks',
        'types',
        'floors',
        'flats',
        'project_meta',
        'tooltips',
        'reservations',
        'homepage_settings',
        'homepage_slides',
        'menu_items',
        'about_stats',
        'about_gallery_images',
        'services',
        'demo_projects',
        'settings',
        'contact_submissions',
    ];

    /**
     * @return array<string, array{label: string, description: string, tables: array<int, string>, directories: array<int, string>, default: bool}>
     */
    public static function groups(): array
    {
        return [
            'real_estate' => [
                'label' => 'Real estate projects',
                'description' => 'Projects, blocks, floors, flats, types, tooltips and all their images.',
                'tables' => ['projects', 'blocks', 'types', 'floors', 'flats', 'project_meta', 'tooltips'],
                'directories' => ['irep', 'projects', '360images', 'blocks', 'floors', 'types', 'flats', 'tooltips'],
                'default' => true,
            ],
            'website' => [
                'label' => 'Website content',
                'description' => 'Homepage, about, services, work and contact pages, navigation menu and their images.',
                'tables' => ['homepage_settings', 'homepage_slides', 'menu_items', 'about_stats', 'about_gallery_images', 'services', 'demo_projects'],
                'directories' => ['homepage-slides', 'menu', 'about-gallery', 'services', 'services-cta', 'services-features', 'services-gallery', 'demo-projects', 'work', 'contact'],
                'default' => true,
            ],
            'settings' => [
                'label' => 'Global settings',
                'description' => 'The IREP key/value settings store.',
                'tables' => ['settings'],
                'directories' => [],
                'default' => true,
            ],
            'reservations' => [
                'label' => 'Reservations',
                'description' => 'Flat reservations. Needs real estate projects to be present too.',
                'tables' => ['reservations'],
                'directories' => [],
                'default' => false,
            ],
            'contact_submissions' => [
                'label' => 'Contact form responses',
                'description' => 'Messages submitted through the public contact form.',
                'tables' => ['contact_submissions'],
                'directories' => [],
                'default' => false,
            ],
            'users' => [
                'label' => 'Admin users',
                'description' => 'Admin accounts and password hashes. Importing this replaces the logins on the target site.',
                'tables' => ['users'],
                'directories' => [],
                'default' => false,
            ],
        ];
    }

    /** @return array<int, string> */
    public static function groupKeys(): array
    {
        return array_keys(static::groups());
    }

    /** @return array<int, string> */
    public static function defaultGroupKeys(): array
    {
        return array_keys(array_filter(static::groups(), fn (array $group) => $group['default']));
    }

    /**
     * Tables for the given groups, in foreign-key-safe insert order.
     *
     * @param  array<int, string>  $groups
     * @return array<int, string>
     */
    public static function tablesFor(array $groups): array
    {
        $all = static::groups();

        $tables = [];
        foreach ($groups as $group) {
            foreach ($all[$group]['tables'] ?? [] as $table) {
                $tables[$table] = true;
            }
        }

        return array_values(array_filter(
            static::TABLE_ORDER,
            fn (string $table) => isset($tables[$table]),
        ));
    }

    /**
     * Public-disk folders for the given groups, plus any top-level folder no
     * group claims — so a new upload directory is never silently left behind.
     *
     * @param  array<int, string>  $groups
     * @return array<int, string>
     */
    public static function directoriesFor(array $groups): array
    {
        $all = static::groups();

        $directories = [];
        foreach ($groups as $group) {
            foreach ($all[$group]['directories'] ?? [] as $directory) {
                $directories[$directory] = true;
            }
        }

        if ($directories !== []) {
            foreach (static::unclaimedDirectories() as $directory) {
                $directories[$directory] = true;
            }
        }

        $directories = array_keys($directories);
        sort($directories);

        return $directories;
    }

    /**
     * Top-level folders on the public disk that no group lists.
     *
     * @return array<int, string>
     */
    public static function unclaimedDirectories(): array
    {
        $claimed = [];
        foreach (static::groups() as $group) {
            foreach ($group['directories'] as $directory) {
                $claimed[$directory] = true;
            }
        }

        return array_values(array_filter(
            Storage::disk('public')->directories(),
            fn (string $directory) => ! isset($claimed[$directory]),
        ));
    }
}
