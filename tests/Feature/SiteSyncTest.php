<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteSync as SiteSyncPage;
use App\Models\MenuItem;
use App\Models\User;
use App\Support\SiteSync\ArchiveParts;
use App\Support\SiteSync\SiteExporter;
use App\Support\SiteSync\SiteImporter;
use App\Support\SiteSync\SyncStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SiteSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        // Archives live on the `local` disk, so faking it keeps test exports
        // out of the developer's real storage/app/private/sync folder.
        Storage::fake('local');
    }

    public function test_export_then_replace_import_restores_rows_and_images(): void
    {
        $item = MenuItem::create([
            'label' => 'Projects',
            'href' => '/projects',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Storage::disk('public')->put('menu/hero.jpg', 'original-bytes');

        $archive = (new SiteExporter)->export(['website'])['path'];

        // Diverge from the archive on every axis the importer has to fix.
        $item->update(['label' => 'Changed locally']);
        MenuItem::create(['label' => 'Extra', 'href' => '/extra', 'sort_order' => 9, 'is_active' => true]);
        Storage::disk('public')->put('menu/hero.jpg', 'overwritten-bytes');

        $result = (new SiteImporter)->import($archive, backup: false);

        $this->assertSame(1, MenuItem::count());
        $this->assertSame('Projects', MenuItem::first()->label);
        $this->assertSame($item->id, MenuItem::first()->id, 'IDs must be preserved so image paths keep resolving.');
        $this->assertSame('original-bytes', Storage::disk('public')->get('menu/hero.jpg'));
        $this->assertSame(1, $result['files']);
    }

    public function test_merge_import_keeps_rows_that_are_only_present_locally(): void
    {
        MenuItem::create(['label' => 'Projects', 'href' => '/projects', 'sort_order' => 1, 'is_active' => true]);

        $archive = (new SiteExporter)->export(['website'], includeFiles: false)['path'];

        MenuItem::create(['label' => 'Local only', 'href' => '/local', 'sort_order' => 2, 'is_active' => true]);

        (new SiteImporter)->import($archive, mode: SiteImporter::MODE_MERGE, backup: false);

        $this->assertSame(2, MenuItem::count());
        $this->assertTrue(MenuItem::where('label', 'Local only')->exists());
    }

    public function test_prune_removes_images_missing_from_the_archive(): void
    {
        Storage::disk('public')->put('menu/keep.jpg', 'keep');

        $archive = (new SiteExporter)->export(['website'])['path'];

        Storage::disk('public')->put('menu/stale.jpg', 'stale');

        (new SiteImporter)->import($archive, pruneFiles: true, backup: false);

        $this->assertTrue(Storage::disk('public')->exists('menu/keep.jpg'));
        $this->assertFalse(Storage::disk('public')->exists('menu/stale.jpg'));
    }

    public function test_a_zip_without_a_manifest_is_rejected(): void
    {
        $path = SyncStorage::path('not-an-export.zip');
        SyncStorage::ensureDirectoryExists();

        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('readme.txt', 'hello');
        $zip->close();

        $this->expectExceptionMessage('no manifest.json');

        (new SiteImporter)->import($path, backup: false);
    }

    public function test_archive_entries_cannot_escape_the_public_disk(): void
    {
        $archive = (new SiteExporter)->export(['website'])['path'];

        $zip = new \ZipArchive;
        $zip->open($archive);
        $zip->addFromString('files/../../../evil.txt', 'pwned');
        $zip->close();

        (new SiteImporter)->import($archive, backup: false);

        $this->assertFileDoesNotExist(storage_path('evil.txt'));
    }

    public function test_the_admin_page_renders_both_forms(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(SiteSyncPage::class)
            ->assertOk()
            ->assertSee('What to export')
            ->assertSee('Restore an archive');
    }

    public function test_archive_download_requires_a_signed_in_user(): void
    {
        $filename = basename((new SiteExporter)->export(['website'], includeFiles: false)['path']);

        $this->get(route('admin.sync.download', ['archive' => $filename]))->assertRedirect('/login');

        $this->actingAs(User::factory()->create())
            ->get(route('admin.sync.download', ['archive' => $filename]))
            ->assertOk()
            ->assertDownload($filename);
    }

    public function test_an_archive_can_be_deleted_from_the_admin_page(): void
    {
        $this->actingAs(User::factory()->create());

        $filename = basename((new SiteExporter)->export(['website'], includeFiles: false)['path']);

        Livewire::test(SiteSyncPage::class)
            ->assertSee($filename)
            ->callAction('deleteArchive', arguments: ['archive' => $filename])
            ->assertHasNoActionErrors()
            ->assertDontSee($filename);

        $this->assertFileDoesNotExist(SyncStorage::path($filename));
    }

    public function test_a_split_export_is_listed_and_deleted_as_one_archive(): void
    {
        $this->actingAs(User::factory()->create());

        Storage::disk('public')->put('menu/hero.jpg', random_bytes(120 * 1024));
        MenuItem::create(['label' => 'Projects', 'href' => '/projects', 'sort_order' => 1, 'is_active' => true]);

        $result = (new SiteExporter)->export(['website'], partSize: 16 * 1024);
        $this->assertGreaterThan(1, count($result['files']));

        $sets = SyncStorage::sets();
        $this->assertCount(1, $sets, 'Every part belongs to the same archive.');
        $this->assertSame($result['filename'], $sets[0]['name']);
        $this->assertTrue($sets[0]['is_split']);
        $this->assertCount(count($result['files']), $sets[0]['parts']);

        Livewire::test(SiteSyncPage::class)
            ->callAction('deleteArchive', arguments: ['archive' => $result['filename']])
            ->assertHasNoActionErrors();

        $this->assertSame([], SyncStorage::files(), 'Deleting the archive removes every part.');
    }

    public function test_delete_all_removes_every_archive(): void
    {
        $this->actingAs(User::factory()->create());

        (new SiteExporter)->export(['website'], includeFiles: false);
        // A second export in the same second reuses the timestamped name, so an
        // older-looking archive is written by hand.
        file_put_contents(SyncStorage::path('flatview-2020-01-01-000000-full.zip'), 'stub');

        $this->assertCount(2, SyncStorage::files());

        Livewire::test(SiteSyncPage::class)
            ->callAction('deleteAllArchives')
            ->assertHasNoActionErrors();

        $this->assertSame([], SyncStorage::files());
    }

    public function test_deleting_cannot_reach_outside_the_sync_folder(): void
    {
        file_put_contents(storage_path('outside.txt'), 'keep me');

        $this->assertFalse(SyncStorage::delete('../outside.txt'));
        $this->assertFileExists(storage_path('outside.txt'));

        unlink(storage_path('outside.txt'));
    }

    public function test_archive_download_cannot_reach_outside_the_sync_folder(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/sync/download/'.urlencode('../../../.env'))
            ->assertNotFound();
    }

    public function test_a_split_export_can_be_reassembled_and_imported(): void
    {
        MenuItem::create(['label' => 'Projects', 'href' => '/projects', 'sort_order' => 1, 'is_active' => true]);

        // Incompressible, so the zip really is larger than the part size.
        $image = random_bytes(120 * 1024);
        Storage::disk('public')->put('menu/hero.jpg', $image);

        $result = (new SiteExporter)->export(['website'], partSize: 16 * 1024);

        $this->assertGreaterThan(1, count($result['files']));
        $this->assertFileDoesNotExist(SyncStorage::path($result['filename']), 'The unsplit archive is replaced by its parts.');

        MenuItem::query()->delete();
        Storage::disk('public')->delete('menu/hero.jpg');

        // Reversed, to prove parts are ordered by number rather than by selection.
        $parts = array_reverse(array_column($result['files'], 'path'));

        (new SiteImporter)->importParts($parts, backup: false);

        $this->assertSame(1, MenuItem::count());
        $this->assertSame($image, Storage::disk('public')->get('menu/hero.jpg'));
    }

    public function test_importing_an_incomplete_set_of_parts_is_refused(): void
    {
        Storage::disk('public')->put('menu/hero.jpg', random_bytes(120 * 1024));

        $parts = array_column((new SiteExporter)->export(['website'], partSize: 16 * 1024)['files'], 'path');

        array_shift($parts);

        $this->expectExceptionMessage('Archive part 001 is missing');

        (new SiteImporter)->importParts($parts, backup: false);
    }

    public function test_split_parts_join_back_to_identical_bytes(): void
    {
        $path = SyncStorage::path('payload.zip');
        SyncStorage::ensureDirectoryExists();
        $bytes = random_bytes(50_000);
        file_put_contents($path, $bytes);

        $parts = ArchiveParts::split($path, 7_000);

        $this->assertCount(8, $parts);
        $this->assertFileDoesNotExist($path);

        ArchiveParts::join($parts, $rejoined = SyncStorage::path('rejoined.zip'));

        $this->assertSame($bytes, file_get_contents($rejoined));
    }

    public function test_manifest_records_what_the_archive_contains(): void
    {
        $manifest = (new SiteExporter)->export(['website'], includeFiles: false)['manifest'];

        $this->assertSame(['website'], $manifest['groups']);
        $this->assertFalse($manifest['includes_files']);
        $this->assertArrayHasKey('menu_items', $manifest['tables']);
    }
}
