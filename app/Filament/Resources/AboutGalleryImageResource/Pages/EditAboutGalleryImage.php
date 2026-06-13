<?php

namespace App\Filament\Resources\AboutGalleryImageResource\Pages;

use App\Filament\Resources\AboutGalleryImageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAboutGalleryImage extends EditRecord
{
    protected static string $resource = AboutGalleryImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
