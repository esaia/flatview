<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AboutStatResource\Pages;
use App\Models\AboutStat;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AboutStatResource extends Resource
{
    protected static ?string $model = AboutStat::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'About Stats';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('value')
                ->required(),

            TextInput::make('label')
                ->required(),

            Toggle::make('is_active')
                ->label('Active')
                ->default(true),

            TextInput::make('sort_order')
                ->numeric()
                ->label('Order')
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('value')
                    ->sortable(),

                TextColumn::make('label')
                    ->searchable(),

                IconColumn::make('is_active')
                    ->boolean(),

                TextColumn::make('sort_order')
                    ->sortable(),
            ])
            ->defaultSort('sort_order', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAboutStats::route('/'),
            'create' => Pages\CreateAboutStat::route('/create'),
            'edit'   => Pages\EditAboutStat::route('/{record}/edit'),
        ];
    }
}
