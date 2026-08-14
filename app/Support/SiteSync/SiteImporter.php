<?php

namespace App\Support\SiteSync;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * Restores an archive produced by {@see SiteExporter} onto this environment.
 *
 * Two modes:
 *   replace — the selected tables end up exactly as they are in the archive
 *             (existing rows are removed first). This is the "make prod look
 *             like local" option.
 *   merge   — rows from the archive are inserted or updated by primary key,
 *             and rows that only exist here are left alone.
 *
 * Images are always written over the top of what is already on the public disk.
 * Nothing on disk is deleted unless $pruneFiles is set.
 */
class SiteImporter
{
    /** Rows inserted per statement. */
    private const CHUNK = 200;

    public const MODE_REPLACE = 'replace';

    public const MODE_MERGE = 'merge';

    /**
     * Import from a set of archive parts, assembling them first when needed.
     *
     * @param  array<int, string>  $parts
     * @param  array<int, string>|null  $groups
     */
    public function importParts(
        array $parts,
        ?array $groups = null,
        string $mode = self::MODE_REPLACE,
        bool $includeFiles = true,
        bool $pruneFiles = false,
        bool $backup = true,
    ): array {
        if (count($parts) === 1 && ! ArchiveParts::isPart($parts[0])) {
            return $this->import($parts[0], $groups, $mode, $includeFiles, $pruneFiles, $backup);
        }

        $assembled = SyncStorage::path(sprintf('assembled-%s.zip', Carbon::now()->format('Y-m-d-His')));
        SyncStorage::ensureDirectoryExists();
        ArchiveParts::join($parts, $assembled);

        try {
            return $this->import($assembled, $groups, $mode, $includeFiles, $pruneFiles, $backup);
        } finally {
            @unlink($assembled);
        }
    }

    /**
     * @param  array<int, string>|null  $groups  Null imports every group in the archive.
     * @return array{manifest: array<string, mixed>, groups: array<int, string>, tables: array<string, int>, files: int, pruned: int, skipped_columns: array<string, array<int, string>>, backup: ?string}
     */
    public function import(
        string $archivePath,
        ?array $groups = null,
        string $mode = self::MODE_REPLACE,
        bool $includeFiles = true,
        bool $pruneFiles = false,
        bool $backup = true,
    ): array {
        if (! is_readable($archivePath)) {
            throw new RuntimeException("The archive {$archivePath} could not be read.");
        }

        if (! in_array($mode, [self::MODE_REPLACE, self::MODE_MERGE], true)) {
            throw new RuntimeException("Unknown import mode [{$mode}].");
        }

        $zip = new ZipArchive;
        if ($zip->open($archivePath) !== true) {
            throw new RuntimeException('The file is not a readable .zip archive.');
        }

        try {
            $manifest = $this->readManifest($zip);

            $available = $manifest['groups'];
            $groups = $groups === null
                ? $available
                : array_values(array_intersect($available, $groups));

            if ($groups === []) {
                throw new RuntimeException('None of the selected content groups are present in this archive.');
            }

            $backupPath = $backup
                ? (new SiteExporter)->export(
                    $groups,
                    includeFiles: false,
                    path: SyncStorage::path(sprintf('pre-import-backup-%s.zip', Carbon::now()->format('Y-m-d-His'))),
                )['path']
                : null;

            $skippedColumns = [];
            $counts = $this->importTables($zip, SyncManifest::tablesFor($groups), $mode, $skippedColumns);

            $files = 0;
            $pruned = 0;
            if ($includeFiles && ($manifest['includes_files'] ?? false)) {
                [$files, $pruned] = $this->importFiles($zip, SyncManifest::directoriesFor($groups), $pruneFiles);
            }

            return [
                'manifest' => $manifest,
                'groups' => $groups,
                'tables' => $counts,
                'files' => $files,
                'pruned' => $pruned,
                'skipped_columns' => $skippedColumns,
                'backup' => $backupPath,
            ];
        } finally {
            $zip->close();
        }
    }

    /** Read the manifest without importing anything — used to preview an archive. */
    public function inspect(string $archivePath): array
    {
        $zip = new ZipArchive;
        if ($zip->open($archivePath) !== true) {
            throw new RuntimeException('The file is not a readable .zip archive.');
        }

        try {
            return $this->readManifest($zip);
        } finally {
            $zip->close();
        }
    }

    /** @return array<string, mixed> */
    private function readManifest(ZipArchive $zip): array
    {
        $raw = $zip->getFromName('manifest.json');

        if ($raw === false) {
            throw new RuntimeException('This archive has no manifest.json — it was not created by the sync exporter.');
        }

        $manifest = json_decode($raw, true);

        if (! is_array($manifest) || ! isset($manifest['format_version'], $manifest['groups'])) {
            throw new RuntimeException('The archive manifest is malformed.');
        }

        if ($manifest['format_version'] > SyncManifest::FORMAT_VERSION) {
            throw new RuntimeException(sprintf(
                'This archive was created by a newer version of the site (format %d, this site reads %d). Update the code on this environment first.',
                $manifest['format_version'],
                SyncManifest::FORMAT_VERSION,
            ));
        }

        return $manifest;
    }

    /**
     * @param  array<int, string>  $tables
     * @param  array<string, array<int, string>>  $skippedColumns
     * @return array<string, int>
     */
    private function importTables(ZipArchive $zip, array $tables, string $mode, array &$skippedColumns): array
    {
        $counts = [];

        DB::transaction(function () use ($zip, $tables, $mode, &$counts, &$skippedColumns) {
            Schema::disableForeignKeyConstraints();

            try {
                if ($mode === self::MODE_REPLACE) {
                    // Children first, so deletes never trip a foreign key.
                    foreach (array_reverse($tables) as $table) {
                        if ($this->hasEntry($zip, $table) && Schema::hasTable($table)) {
                            DB::table($table)->delete();
                        }
                    }
                }

                foreach ($tables as $table) {
                    if (! $this->hasEntry($zip, $table)) {
                        continue;
                    }

                    if (! Schema::hasTable($table)) {
                        throw new RuntimeException("The archive contains table [{$table}], which does not exist here. Run `php artisan migrate` first.");
                    }

                    $counts[$table] = $this->importTable($zip, $table, $mode, $skippedColumns);
                }
            } finally {
                Schema::enableForeignKeyConstraints();
            }
        });

        return $counts;
    }

    /**
     * @param  array<string, array<int, string>>  $skippedColumns
     */
    private function importTable(ZipArchive $zip, string $table, string $mode, array &$skippedColumns): int
    {
        $rows = json_decode((string) $zip->getFromName("database/{$table}.json"), true);

        if (! is_array($rows)) {
            throw new RuntimeException("The data for table [{$table}] is malformed.");
        }

        if ($rows === []) {
            return 0;
        }

        $columns = Schema::getColumnListing($table);

        // Tolerate schema drift between environments: keep only columns that
        // exist here, and report the rest instead of failing the whole import.
        $missing = array_values(array_diff(array_keys($rows[0]), $columns));
        if ($missing !== []) {
            $skippedColumns[$table] = $missing;
        }

        $rows = array_map(
            fn (array $row) => array_intersect_key($row, array_flip($columns)),
            $rows,
        );

        $hasId = in_array('id', $columns, true);

        foreach (array_chunk($rows, self::CHUNK) as $chunk) {
            if ($mode === self::MODE_MERGE && $hasId) {
                $updatable = array_values(array_diff(array_keys($chunk[0]), ['id']));
                DB::table($table)->upsert($chunk, ['id'], $updatable);
            } else {
                DB::table($table)->insert($chunk);
            }
        }

        return count($rows);
    }

    private function hasEntry(ZipArchive $zip, string $table): bool
    {
        return $zip->locateName("database/{$table}.json") !== false;
    }

    /**
     * @param  array<int, string>  $directories
     * @return array{0: int, 1: int} [written, pruned]
     */
    private function importFiles(ZipArchive $zip, array $directories, bool $pruneFiles): array
    {
        $disk = Storage::disk('public');
        $written = 0;
        $imported = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);

            if ($name === false || ! str_starts_with($name, 'files/') || str_ends_with($name, '/')) {
                continue;
            }

            $relative = substr($name, strlen('files/'));

            if ($relative === '' || ! $this->isSafePath($relative)) {
                continue;
            }

            if (! $this->belongsTo($relative, $directories)) {
                continue;
            }

            $stream = $zip->getStream($name);
            if ($stream === false) {
                continue;
            }

            $disk->writeStream($relative, $stream);
            fclose($stream);

            $imported[$relative] = true;
            $written++;
        }

        $pruned = 0;
        if ($pruneFiles) {
            foreach ($directories as $directory) {
                if (! $disk->directoryExists($directory)) {
                    continue;
                }

                foreach ($disk->allFiles($directory) as $file) {
                    if (! isset($imported[$file])) {
                        $disk->delete($file);
                        $pruned++;
                    }
                }
            }
        }

        return [$written, $pruned];
    }

    /** Reject archive entries that would escape the public disk. */
    private function isSafePath(string $relative): bool
    {
        if (str_contains($relative, "\0") || str_starts_with($relative, '/')) {
            return false;
        }

        foreach (explode('/', $relative) as $segment) {
            if ($segment === '..') {
                return false;
            }
        }

        return true;
    }

    /** @param  array<int, string>  $directories */
    private function belongsTo(string $relative, array $directories): bool
    {
        foreach ($directories as $directory) {
            if (str_starts_with($relative, $directory.'/')) {
                return true;
            }
        }

        return false;
    }
}
