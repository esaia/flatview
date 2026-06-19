<?php

namespace App\Filament\Resources\HomepageSlideResource\Pages;

use App\Filament\Resources\HomepageSlideResource;
use App\Filament\Widgets\HomepageTextWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHomepageSlides extends ListRecords
{
    protected static string $resource = HomepageSlideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New slide'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            HomepageTextWidget::class,
        ];
    }
}
