<?php

namespace App\Support\SiteSync;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * Packs selected database tables and their uploaded images into a single .zip
 * that can be restored on another environment by {@see SiteImporter}.
 *
 * Archive layout:
 *   manifest.json          — what is inside, and which format version wrote it
 *   database/<table>.json  — every row of that table, as an array of objects
 *   files/<path>           — files copied verbatim from the `public` disk
 */
class SiteExporter
{
    /** Rows fetched per query, to keep memory flat on large tables. */
    private const CHUNK = 500;

    /**
     * @param  array<int, string>  $groups  Keys from {@see SyncManifest::groups()}
     * @param  int|null  $partSize  Split the result into parts of at most this many bytes,
     *                              so each one fits through the target's upload limit.
     * @return array{path: string, filename: string, files: array<int, array{filename: string, path: string, bytes: int}>, manifest: array<string, mixed>}
     */
    public function export(array $groups, bool $includeFiles = true, ?string $path = null, ?int $partSize = null): array
    {
        $groups = array_values(array_intersect(SyncManifest::groupKeys(), $groups));

        if ($groups === []) {
            throw new RuntimeException('Select at least one content group to export.');
        }

        $filename = $path === null
            ? sprintf('flatview-%s-%s.zip', Carbon::now()->format('Y-m-d-His'), $includeFiles ? 'full' : 'data')
            : basename($path);

        $path ??= SyncStorage::path($filename);
        SyncStorage::ensureDirectoryExists(dirname($path));

        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Could not create the archive at {$path}.");
        }

        $tables = $this->writeTables($zip, SyncManifest::tablesFor($groups));
        $files = $includeFiles
            ? $this->writeFiles($zip, SyncManifest::directoriesFor($groups))
            : ['count' => 0, 'bytes' => 0];

        $manifest = [
            'format_version' => SyncManifest::FORMAT_VERSION,
            'exported_at' => Carbon::now()->toIso8601String(),
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
            'groups' => $groups,
            'includes_files' => $includeFiles,
            'tables' => $tables,
            'file_count' => $files['count'],
            'file_bytes' => $files['bytes'],
        ];

        $zip->addFromString('manifest.json', $this->encode($manifest));
        $zip->close();

        $produced = ($partSize !== null && filesize($path) > $partSize)
            ? ArchiveParts::split($path, $partSize)
            : [$path];

        return [
            'path' => $produced[0],
            'filename' => $filename,
            'files' => array_map(fn (string $file) => [
                'filename' => basename($file),
                'path' => $file,
                'bytes' => (int) filesize($file),
            ], $produced),
            'manifest' => $manifest,
        ];
    }

    /**
     * @param  array<int, string>  $tables
     * @return array<string, int> Table name => exported row count
     */
    private function writeTables(ZipArchive $zip, array $tables): array
    {
        $counts = [];

        foreach ($tables as $table) {
            if (! $this->tableExists($table)) {
                continue;
            }

            $rows = [];
            DB::table($table)->orderBy($this->orderColumn($table))->chunk(self::CHUNK, function ($chunk) use (&$rows) {
                foreach ($chunk as $row) {
                    $rows[] = (array) $row;
                }
            });

            $zip->addFromString("database/{$table}.json", $this->encode($rows));
            $counts[$table] = count($rows);
        }

        return $counts;
    }

    /**
     * @param  array<int, string>  $directories
     * @return array{count: int, bytes: int}
     */
    private function writeFiles(ZipArchive $zip, array $directories): array
    {
        $disk = Storage::disk('public');
        $count = 0;
        $bytes = 0;

        foreach ($directories as $directory) {
            if (! $disk->directoryExists($directory)) {
                continue;
            }

            foreach ($disk->allFiles($directory) as $file) {
                $absolute = $disk->path($file);

                if (! is_readable($absolute)) {
                    continue;
                }

                $zip->addFile($absolute, "files/{$file}");
                $count++;
                $bytes += (int) filesize($absolute);
            }
        }

        return ['count' => $count, 'bytes' => $bytes];
    }

    private function tableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }

    /** Order by primary key when there is one, so exports are reproducible. */
    private function orderColumn(string $table): string
    {
        $columns = DB::getSchemaBuilder()->getColumnListing($table);

        return in_array('id', $columns, true) ? 'id' : $columns[0];
    }

    private function encode(mixed $value): string
    {
        return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
