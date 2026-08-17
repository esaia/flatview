<?php

namespace App\Filament\Support;

use App\Filament\Forms\Components\MediaGridField;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Radio;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * WordPress-style media picker. Attach `MediaLibrary::pickerAction()` as a
 * hint action on any FileUpload to let editors reuse images that have already
 * been uploaded to the public disk instead of uploading a fresh copy.
 */
class MediaLibrary
{
    /** Image extensions shown in the library grid. */
    protected const EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'];

    public static function pickerAction(string $name = 'mediaLibrary'): Action
    {
        return Action::make($name)
            ->label('Choose from library')
            ->icon('heroicon-m-photo')
            ->modalHeading('Media library')
            ->modalDescription(fn (Field $component): string => static::isMultiple($component)
                ? 'Select one or more images you have already uploaded.'
                : 'Select an image you have already uploaded.')
            ->modalSubmitActionLabel(fn (Field $component): string => static::isMultiple($component)
                ? 'Use selected'
                : 'Use image')
            ->modalWidth('5xl')
            ->fillForm(fn (Field $component): array => [
                'media_selection' => [],
                'media_folder' => static::directoryFor($component) ?? '',
            ])
            ->schema(fn (Field $component): array => [
                // Opens on the field's own folder, but any folder can be browsed
                // — an image is often worth reusing across sections. Uploads
                // still land in the field's directory.
                Radio::make('media_folder')
                    ->label('Folder')
                    ->options(fn (): array => static::folderOptions(static::directoryFor($component)))
                    ->default(static::directoryFor($component) ?? '')
                    ->inline()
                    ->live(),

                MediaGridField::make('media_selection')
                    ->hiddenLabel()
                    ->multiSelect(static::isMultiple($component))
                    ->images(fn (Get $get): array => static::images($get('media_folder'))),
            ])
            ->action(function (array $data, Field $component): void {
                $selection = array_values(array_filter((array) ($data['media_selection'] ?? [])));

                if ($selection === []) {
                    return;
                }

                if (static::isMultiple($component)) {
                    $paths = array_values($component->getState() ?? []);
                    foreach ($selection as $path) {
                        if (! in_array($path, $paths, true)) {
                            $paths[] = $path;
                        }
                    }
                } else {
                    // Single-file upload: keep only the last picked image.
                    $paths = [end($selection)];
                }

                $component->state(
                    collect($paths)
                        ->mapWithKeys(fn (string $path): array => [(string) Str::uuid() => $path])
                        ->all()
                );
            });
    }

    /**
     * Delete a file a section has stopped using, but only when it lives in that
     * section's own upload directory. Anything picked from another folder
     * belongs to another section — the same image is often shared — and must
     * survive being dropped here.
     */
    public static function deleteIfOwned(?string $path, string $directory): void
    {
        $path = is_string($path) ? ltrim($path, '/') : null;

        if (blank($path) || ! str_starts_with($path, trim($directory, '/').'/')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    /**
     * Delete an image outright from the public disk, for the trash button on a
     * library tile. Unlike `deleteIfOwned()` this is not scoped to one field's
     * folder — the editor is managing the library itself — so the path is
     * checked to be an image inside the disk and nothing else.
     */
    public static function delete(string $path): bool
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        if ($path === '' || str_contains($path, '..') || ! static::isImage($path)) {
            return false;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return false;
        }

        return $disk->delete($path);
    }

    /** Whether the target FileUpload accepts multiple files. */
    protected static function isMultiple(Field $component): bool
    {
        return method_exists($component, 'isMultiple') && $component->isMultiple();
    }

    /**
     * The upload directory of the target FileUpload, so the library only shows
     * images belonging to that field (e.g. `services-gallery`).
     */
    protected static function directoryFor(Field $component): ?string
    {
        if (! method_exists($component, 'getDirectory')) {
            return null;
        }

        $directory = $component->getDirectory();

        return is_string($directory) ? trim($directory, '/') : null;
    }

    /**
     * Folders on the public disk that hold images, as select options. The
     * field's own folder is listed first and marked, since that is where its
     * uploads go; "All folders" is last because it can be a long list.
     *
     * @return array<string, string>
     */
    protected static function folderOptions(?string $current): array
    {
        $disk = Storage::disk('public');

        $counts = collect($disk->allFiles())
            ->filter(fn (string $path): bool => static::isImage($path))
            ->groupBy(fn (string $path): string => trim(dirname($path), '.'))
            ->map(fn ($files): int => $files->count())
            ->filter(fn (int $count, string $folder): bool => $folder !== '')
            ->sortKeys();

        $options = [];

        if (filled($current)) {
            $options[$current] = $current.' (this field) — '.($counts[$current] ?? 0);
        }

        foreach ($counts as $folder => $count) {
            if ($folder === $current) {
                continue;
            }

            $options[$folder] = $folder.' — '.$count;
        }

        $options[''] = 'All folders — '.$counts->sum();

        return $options;
    }

    protected static function isImage(string $path): bool
    {
        return in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::EXTENSIONS, true);
    }

    /**
     * Images stored on the public disk, newest first. When a directory is
     * given, only images inside that directory are returned; the picker opens
     * on the target field's own folder and can switch to any other.
     *
     * @return array<int, array{path: string, url: string, name: string}>
     */
    public static function images(?string $directory = null): array
    {
        $disk = Storage::disk('public');

        $files = ($directory !== null && $directory !== '')
            ? $disk->files($directory)
            : $disk->allFiles();

        return collect($files)
            ->filter(fn (string $path): bool => static::isImage($path))
            ->sortByDesc(fn (string $path): int => $disk->lastModified($path))
            ->map(fn (string $path): array => [
                'path' => $path,
                'url' => $disk->url($path),
                'name' => basename($path),
            ])
            ->values()
            ->all();
    }
}
