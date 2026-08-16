<?php

namespace App\Filament\Resources\DemoProjects\Pages;

use App\Filament\Resources\DemoProjects\DemoProjectResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDemoProject extends CreateRecord
{
    protected static string $resource = DemoProjectResource::class;
}
