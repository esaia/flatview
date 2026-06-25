<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Imagick;

/**
 * Downscales and recompresses raster images on the public disk IN PLACE,
 * keeping the exact same path and extension so no DB rows or front-end
 * references need to change. JPEGs/PNGs/WebPs that are wider than the target
 * or heavier than they need to be are shrunk; everything else is left alone.
 *
 * Safe by default: runs as a dry-run (reports only) unless --write is passed,
 * and --write backs up every original it touches under storage/app/<backup>
 * first, so the operation is reversible.
 */
class OptimizeImages extends Command
{
    protected $signature = 'images:optimize
        {--write : Actually rewrite files (otherwise dry-run preview only)}
        {--max-width=2000 : Downscale anything wider than this many pixels}
        {--quality=82 : JPEG/WebP encode quality (1-100)}
        {--min-kb=150 : Skip files already smaller than this}
        {--path= : Limit to a sub-path of the public disk, e.g. menu}';

    protected $description = 'Downscale + recompress oversized public images in place (with backup)';

    private string $publicRoot;
    private string $backupRoot;

    public function handle(): int
    {
        if (! extension_loaded('imagick')) {
            $this->error('The imagick PHP extension is required.');
            return self::FAILURE;
        }

        $write     = (bool) $this->option('write');
        $maxWidth  = (int) $this->option('max-width');
        $quality   = (int) $this->option('quality');
        $minBytes  = (int) $this->option('min-kb') * 1024;

        $this->publicRoot = storage_path('app/public');
        $this->backupRoot = storage_path('app/image-optimize-backup');

        $base = $this->publicRoot;
        if ($sub = $this->option('path')) {
            $base = $base.DIRECTORY_SEPARATOR.trim($sub, '/');
        }

        if (! is_dir($base)) {
            $this->error("Path not found: {$base}");
            return self::FAILURE;
        }

        $files = $this->rasterFiles($base);
        $this->info(sprintf('Scanning %d images under %s', count($files), $base));
        $this->line($write
            ? '<fg=yellow>WRITE mode — files will be replaced (originals backed up).</>'
            : '<fg=cyan>DRY RUN — no files changed. Re-run with --write to apply.</>');
        $this->newLine();

        $totalBefore = 0;
        $totalAfter  = 0;
        $changed     = 0;

        foreach ($files as $file) {
            $before = filesize($file);

            // Skip files that are already small; nothing to gain.
            if ($before < $minBytes) {
                continue;
            }

            try {
                $result = $this->process($file, $maxWidth, $quality, $write);
            } catch (\Throwable $e) {
                $this->warn('  skip '.$this->rel($file).' — '.$e->getMessage());
                continue;
            }

            if ($result === null) {
                continue; // no worthwhile saving
            }

            $totalBefore += $before;
            $totalAfter  += $result;
            $changed++;

            $this->line(sprintf(
                '  %-55s %8s → %8s  (-%d%%)',
                $this->rel($file),
                $this->human($before),
                $this->human($result),
                $before > 0 ? round((1 - $result / $before) * 100) : 0,
            ));
        }

        $this->newLine();
        $this->info(sprintf(
            '%d image(s) %s: %s → %s  (saved %s)',
            $changed,
            $write ? 'optimized' : 'would be optimized',
            $this->human($totalBefore),
            $this->human($totalAfter),
            $this->human(max(0, $totalBefore - $totalAfter)),
        ));

        if (! $write && $changed > 0) {
            $this->newLine();
            $this->line('Run <fg=green>php artisan images:optimize --write</> to apply (originals saved to storage/app/image-optimize-backup).');
        }

        return self::SUCCESS;
    }

    /**
     * Resize/recompress one file. Returns the new byte size, or null if the
     * re-encode wouldn't be meaningfully smaller (so we leave the original).
     */
    private function process(string $file, int $maxWidth, int $quality, bool $write): ?int
    {
        $img = new Imagick($file);
        $this->autoOrient($img);

        $width = $img->getImageWidth();
        if ($width > $maxWidth) {
            $img->resizeImage($maxWidth, 0, Imagick::FILTER_LANCZOS, 1);
        }

        $img->stripImage(); // drop EXIF/metadata

        $format = strtolower($img->getImageFormat());
        if (str_contains($format, 'png')) {
            // Keep PNG (lossless) but maximise compression.
            $img->setImageCompressionQuality(95);
            $img->setOption('png:compression-level', '9');
            $img->setOption('png:compression-filter', '5');
        } else {
            // JPEG / WebP — lossy, progressive.
            $img->setImageCompressionQuality($quality);
            if (str_contains($format, 'jpeg') || str_contains($format, 'jpg')) {
                $img->setInterlaceScheme(Imagick::INTERLACE_PLANE);
            }
        }

        $blob    = $img->getImageBlob();
        $newSize = strlen($blob);
        $img->clear();
        $img->destroy();

        // Require at least 8% smaller to bother rewriting (avoids churn on
        // files that are already near-optimal).
        if ($newSize >= filesize($file) * 0.92) {
            return null;
        }

        if ($write) {
            $this->backup($file);
            file_put_contents($file, $blob);
        }

        return $newSize;
    }

    /**
     * Apply EXIF orientation then reset the flag. Older Imagick builds lack
     * autoOrientImage(), so do it manually and degrade gracefully.
     */
    private function autoOrient(Imagick $img): void
    {
        if (method_exists($img, 'autoOrientImage')) {
            $img->autoOrientImage();
            return;
        }

        switch ($img->getImageOrientation()) {
            case Imagick::ORIENTATION_BOTTOMRIGHT:
                $img->rotateImage('#000', 180);
                break;
            case Imagick::ORIENTATION_RIGHTTOP:
                $img->rotateImage('#000', 90);
                break;
            case Imagick::ORIENTATION_LEFTBOTTOM:
                $img->rotateImage('#000', -90);
                break;
        }
        $img->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
    }

    private function backup(string $file): void
    {
        $dest = $this->backupRoot.DIRECTORY_SEPARATOR.$this->rel($file);
        if (! is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0775, true);
        }
        // Only back up the first time we touch a given file.
        if (! file_exists($dest)) {
            copy($file, $dest);
        }
    }

    /** @return string[] */
    private function rasterFiles(string $base): array
    {
        $out = [];
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($it as $f) {
            if (! $f->isFile()) {
                continue;
            }
            $ext = strtolower($f->getExtension());
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $out[] = $f->getPathname();
            }
        }
        sort($out);
        return $out;
    }

    private function rel(string $file): string
    {
        return ltrim(str_replace($this->publicRoot, '', $file), DIRECTORY_SEPARATOR);
    }

    private function human(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }
        return round($bytes / 1024).' KB';
    }
}
