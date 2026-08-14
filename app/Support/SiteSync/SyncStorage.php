<?php

namespace App\Support\SiteSync;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Where sync archives live: the `sync` folder of the private `local` disk
 * (storage/app/private/sync), so an archive is never reachable over HTTP.
 */
class SyncStorage
{
    /** Path relative to the `local` disk, as used by the admin upload field. */
    public const DIRECTORY = 'sync';

    public static function directory(): string
    {
        return Storage::disk('local')->path(self::DIRECTORY);
    }

    public static function path(string $filename): string
    {
        return static::directory().'/'.basename($filename);
    }

    public static function ensureDirectoryExists(?string $directory = null): string
    {
        $directory ??= static::directory();

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        return $directory;
    }

    /**
     * Archives already sitting on this server, newest first.
     *
     * @return array<string, string> Absolute path => human-readable label
     */
    public static function available(): array
    {
        static::ensureDirectoryExists();

        $files = collect(File::files(static::directory()))
            ->filter(fn ($file) => strtolower($file->getExtension()) === 'zip' || ArchiveParts::isPart($file->getFilename()))
            ->sortByDesc(fn ($file) => $file->getMTime());

        return $files->mapWithKeys(fn ($file) => [
            $file->getPathname() => sprintf(
                '%s (%s, %s)',
                $file->getFilename(),
                static::humanSize($file->getSize()),
                date('Y-m-d H:i', $file->getMTime()),
            ),
        ])->all();
    }

    public static function humanSize(int|float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return round($bytes, $index === 0 ? 0 : 1).' '.$units[$index];
    }
}
