<?php

namespace App\Filament\Resources\DemoProjects;

use App\Filament\Resources\DemoProjects\Pages\CreateDemoProject;
use App\Filament\Resources\DemoProjects\Pages\EditDemoProject;
use App\Filament\Resources\DemoProjects\Pages\ListDemoProjects;
use App\Filament\Resources\DemoProjects\Schemas\DemoProjectForm;
use App\Filament\Resources\DemoProjects\Tables\DemoProjectsTable;
use App\Models\DemoProject;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DemoProjectResource extends Resource
{
    protected static ?string $model = DemoProject::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Demo Projects';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'demo project';

    public static function form(Schema $schema): Schema
    {
        return DemoProjectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DemoProjectsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDemoProjects::route('/'),
            'create' => CreateDemoProject::route('/create'),
            'edit' => EditDemoProject::route('/{record}/edit'),
        ];
    }
}
