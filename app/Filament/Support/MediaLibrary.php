<?php

namespace App\Filament\Support;

use App\Filament\Forms\Components\MediaGridField;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
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
            ->fillForm(['media_selection' => []])
            ->schema(fn (Field $component): array => [
                MediaGridField::make('media_selection')
                    ->hiddenLabel()
                    ->multiSelect(static::isMultiple($component))
                    ->images(fn (): array => static::images()),
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

    /** Whether the target FileUpload accepts multiple files. */
    protected static function isMultiple(Field $component): bool
    {
        return method_exists($component, 'isMultiple') && $component->isMultiple();
    }

    /**
     * Every image currently stored on the public disk, newest first.
     *
     * @return array<int, array{path: string, url: string, name: string}>
     */
    public static function images(): array
    {
        $disk = Storage::disk('public');

        return collect($disk->allFiles())
            ->filter(fn (string $path): bool => in_array(
                strtolower(pathinfo($path, PATHINFO_EXTENSION)),
                self::EXTENSIONS,
                true,
            ))
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
