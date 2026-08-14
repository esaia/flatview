<?php

namespace App\Http\Controllers;

use App\Support\SiteSync\ArchiveParts;
use App\Support\SiteSync\SyncStorage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Streams a sync archive to a signed-in admin.
 *
 * Downloads go through this instead of being returned from the Livewire page,
 * because Livewire base64-encodes a returned file into the response payload —
 * fine for a 150 KB data export, fatal for a 150 MB one with images.
 */
class SyncArchiveController extends Controller
{
    public function __invoke(string $archive): BinaryFileResponse
    {
        // basename() alone is the guard: the name never becomes a path.
        $path = SyncStorage::path(basename($archive));

        $isArchive = str_ends_with(strtolower($path), '.zip') || ArchiveParts::isPart($path);

        abort_unless(is_file($path) && $isArchive, 404);

        return response()->download($path);
    }
}
