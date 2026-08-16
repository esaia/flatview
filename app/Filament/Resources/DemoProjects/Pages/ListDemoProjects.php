<?php

namespace App\Filament\Resources\DemoProjects\Pages;

use App\Filament\Resources\DemoProjects\DemoProjectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDemoProjects extends ListRecords
{
    protected static string $resource = DemoProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
