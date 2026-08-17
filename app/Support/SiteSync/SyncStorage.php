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
     * @return array<int, array{filename: string, path: string, bytes: int, size: string, modified: string, set: string}>
     */
    public static function files(): array
    {
        static::ensureDirectoryExists();

        return collect(File::files(static::directory()))
            ->filter(fn ($file) => strtolower($file->getExtension()) === 'zip' || ArchiveParts::isPart($file->getFilename()))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->map(fn ($file) => [
                'filename' => $file->getFilename(),
                'path' => $file->getPathname(),
                'bytes' => (int) $file->getSize(),
                'size' => static::humanSize($file->getSize()),
                'modified' => date('Y-m-d H:i', $file->getMTime()),
                'set' => static::setName($file->getFilename()),
            ])
            ->values()
            ->all();
    }

    /**
     * The archive a file belongs to: the parts of a split export all share the
     * name of the single .zip they were sliced from.
     */
    public static function setName(string $filename): string
    {
        $filename = basename($filename);

        return ArchiveParts::isPart($filename)
            ? (string) preg_replace(ArchiveParts::PATTERN, '.zip', $filename)
            : $filename;
    }

    /**
     * Archives grouped into what an admin actually thinks of as one backup:
     * a single .zip, or the complete set of parts it was split into.
     *
     * @return array<int, array{
     *     name: string,
     *     parts: array<int, array{filename: string, path: string, bytes: int, size: string, modified: string, set: string}>,
     *     size: string,
     *     modified: string,
     *     is_split: bool,
     * }>
     */
    public static function sets(): array
    {
        return collect(static::files())
            ->groupBy('set')
            ->map(function ($parts, string $name) {
                $parts = $parts->sortBy('filename')->values();

                return [
                    'name' => $name,
                    'parts' => $parts->all(),
                    'size' => static::humanSize($parts->sum('bytes')),
                    'modified' => $parts->max('modified'),
                    'is_split' => $parts->count() > 1 || ArchiveParts::isPart($parts->first()['filename']),
                ];
            })
            ->sortByDesc('modified')
            ->values()
            ->all();
    }

    /**
     * Remove a whole archive: the single .zip, or every part of a split export.
     * Half a set is worthless, so parts are never deleted individually.
     *
     * @return int Files removed
     */
    public static function deleteSet(string $name): int
    {
        $name = static::setName($name);

        $deleted = 0;

        foreach (static::files() as $file) {
            if ($file['set'] === $name) {
                $deleted += (int) static::delete($file['filename']);
            }
        }

        return $deleted;
    }

    /**
     * The same archives, shaped for a select field.
     *
     * @return array<string, string> Absolute path => human-readable label
     */
    public static function available(): array
    {
        return collect(static::files())
            ->mapWithKeys(fn (array $file) => [
                $file['path'] => sprintf('%s (%s, %s)', $file['filename'], $file['size'], $file['modified']),
            ])
            ->all();
    }

    /**
     * Remove one archive or part. The name never becomes a path, so an admin
     * cannot reach outside the sync folder.
     */
    public static function delete(string $filename): bool
    {
        $path = static::path($filename);

        $isArchive = str_ends_with(strtolower($path), '.zip') || ArchiveParts::isPart($path);

        if (! $isArchive || ! is_file($path)) {
            return false;
        }

        return File::delete($path);
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
