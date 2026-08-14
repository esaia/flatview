<?php

namespace App\Support\SiteSync;

use RuntimeException;

/**
 * Splits an archive into upload-sized parts and joins them back together.
 *
 * A browser upload cannot exceed PHP's `upload_max_filesize` / `post_max_size`,
 * and Livewire does not chunk uploads — so a full archive with images would be
 * impossible to import through the admin panel on most hosts. Parts are plain
 * byte slices named `<archive>.zip.001`, `.002`, …; concatenating them in order
 * reproduces the original file exactly.
 */
class ArchiveParts
{
    /** Bytes moved per read/write, so splitting a large archive stays memory-flat. */
    private const BUFFER = 1024 * 1024;

    /** Matches a part produced by {@see split()}. */
    public const PATTERN = '/\.zip\.(\d{3})$/';

    /**
     * Replace an archive with numbered parts of at most $partSize bytes.
     *
     * @return array<int, string> Absolute part paths, in order
     */
    public static function split(string $path, int $partSize): array
    {
        if ($partSize < 1) {
            throw new RuntimeException('The part size must be at least one byte.');
        }

        $source = fopen($path, 'rb');
        if ($source === false) {
            throw new RuntimeException("Could not read {$path} to split it.");
        }

        $parts = [];

        try {
            $index = 0;
            while (! feof($source)) {
                $written = 0;
                $partPath = sprintf('%s.%03d', $path, ++$index);
                $target = fopen($partPath, 'wb');

                if ($target === false) {
                    throw new RuntimeException("Could not write archive part {$partPath}.");
                }

                try {
                    while ($written < $partSize && ! feof($source)) {
                        $buffer = fread($source, (int) min(self::BUFFER, $partSize - $written));

                        if ($buffer === false || $buffer === '') {
                            break;
                        }

                        fwrite($target, $buffer);
                        $written += strlen($buffer);
                    }
                } finally {
                    fclose($target);
                }

                // A final read landing exactly on the boundary leaves an empty part.
                if ($written === 0) {
                    @unlink($partPath);

                    break;
                }

                $parts[] = $partPath;
            }
        } finally {
            fclose($source);
        }

        @unlink($path);

        return $parts;
    }

    /**
     * Concatenate parts into a single archive at $target.
     *
     * @param  array<int, string>  $parts
     */
    public static function join(array $parts, string $target): string
    {
        $parts = static::sort($parts);

        if ($parts === []) {
            throw new RuntimeException('No archive parts were given.');
        }

        static::assertComplete($parts);

        $handle = fopen($target, 'wb');
        if ($handle === false) {
            throw new RuntimeException("Could not assemble the archive at {$target}.");
        }

        try {
            foreach ($parts as $part) {
                $source = fopen($part, 'rb');

                if ($source === false) {
                    throw new RuntimeException('Could not read archive part '.basename($part).'.');
                }

                stream_copy_to_stream($source, $handle);
                fclose($source);
            }
        } finally {
            fclose($handle);
        }

        return $target;
    }

    public static function isPart(string $path): bool
    {
        return (bool) preg_match(self::PATTERN, $path);
    }

    /**
     * Order parts by their number, not by upload order — the admin can pick
     * them in any order in the file dialog.
     *
     * @param  array<int, string>  $parts
     * @return array<int, string>
     */
    public static function sort(array $parts): array
    {
        $parts = array_values($parts);

        usort($parts, fn (string $a, string $b) => static::number($a) <=> static::number($b));

        return $parts;
    }

    /** @param  array<int, string>  $parts */
    private static function assertComplete(array $parts): void
    {
        $expected = 1;

        foreach ($parts as $part) {
            $number = static::number($part);

            if ($number !== $expected) {
                throw new RuntimeException(sprintf(
                    'Archive part %03d is missing — select every part of the set before importing.',
                    $expected,
                ));
            }

            $expected++;
        }
    }

    private static function number(string $path): int
    {
        return preg_match(self::PATTERN, $path, $matches) ? (int) $matches[1] : 0;
    }
}
