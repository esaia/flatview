<?php

namespace App\Filament\Resources\DemoProjects\Pages;

use App\Filament\Resources\DemoProjects\DemoProjectResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDemoProject extends EditRecord
{
    protected static string $resource = DemoProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
