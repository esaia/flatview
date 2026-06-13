<?php

namespace App\Filament\Resources\AboutGalleryImageResource\Pages;

use App\Filament\Resources\AboutGalleryImageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAboutGalleryImages extends ListRecords
{
    protected static string $resource = AboutGalleryImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
