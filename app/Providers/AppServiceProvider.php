<?php

namespace App\Providers;

use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use IrepPlugin\FilamentIrep\Support\ImageOptimizer;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        $this->optimizeFileUploads();
    }

    /**
     * Re-encode every image uploaded through a Filament `FileUpload` to WebP
     * before it lands on the disk. Registered globally so admin fields in both
     * `app/Filament` and the IREP package are covered without each call site
     * having to opt in.
     */
    protected function optimizeFileUploads(): void
    {
        FileUpload::configureUsing(function (FileUpload $upload): void {
            $upload->saveUploadedFileUsing(function (FileUpload $component, TemporaryUploadedFile $file): ?string {
                if (! ImageOptimizer::isConvertible($file->getMimeType())) {
                    return $component->saveUploadedFile($file);
                }

                $binary = ImageOptimizer::toWebp($file->getRealPath());

                if ($binary === null) {
                    return $component->saveUploadedFile($file);
                }

                // Keep the component's own naming rules, only swapping the
                // extension, and keep the file in the field's own directory so
                // the media-library picker still finds it. The name closure can
                // generate a random ULID, so resolve it exactly once.
                $name = $component->getUploadedFileNameForStorage($file);
                $extension = pathinfo($name, PATHINFO_EXTENSION);
                $name = $extension === ''
                    ? $name.'.webp'
                    : Str::replaceLast('.'.$extension, '.webp', $name);

                $path = trim($component->getDirectory().'/'.$name, '/');

                $component->getDisk()->put($path, $binary, $component->getVisibility());

                return $path;
            });
        });
    }
}
