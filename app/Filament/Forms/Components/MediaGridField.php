<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;

/**
 * Renders a clickable grid of previously uploaded images. Its state is an
 * array of selected disk-relative paths, surfaced in the parent action's
 * form data so the media-library picker can apply them to a FileUpload.
 */
class MediaGridField extends Field
{
    protected string $view = 'filament.components.media-grid';

    /** @var array<int, array{path: string, url: string, name: string}> | Closure */
    protected array|Closure $images = [];

    protected bool|Closure $isMultiSelect = false;

    public function multiSelect(bool|Closure $condition = true): static
    {
        $this->isMultiSelect = $condition;

        return $this;
    }

    public function isMultiSelect(): bool
    {
        return (bool) $this->evaluate($this->isMultiSelect);
    }

    /**
     * @param  array<int, array{path: string, url: string, name: string}> | Closure  $images
     */
    public function images(array|Closure $images): static
    {
        $this->images = $images;

        return $this;
    }

    /**
     * @return array<int, array{path: string, url: string, name: string}>
     */
    public function getImages(): array
    {
        return $this->evaluate($this->images);
    }
}
