<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Project;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Storage;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static string|\UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Homepage Slides';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->required(),

            TextInput::make('category')
                ->required(),

            TextInput::make('year')
                ->numeric()
                ->minLength(4)
                ->maxLength(4)
                ->placeholder('2024'),

            Select::make('media_type')
                ->options(['image' => 'Image', 'video' => 'Video'])
                ->default('image')
                ->live(),

            FileUpload::make('image')
                ->disk('public')
                ->directory('projects')
                ->visibility('public')
                ->image()
                ->imagePreviewHeight('200')
                ->downloadable()
                ->openable()
                ->nullable()
                ->hidden(fn (Get $get) => $get('media_type') === 'video'),

            FileUpload::make('video')
                ->label('Video (optional)')
                ->disk('public')
                ->directory('projects/videos')
                ->visibility('public')
                ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/mov'])
                ->downloadable()
                ->openable()
                ->nullable()
                ->hidden(fn (Get $get) => $get('media_type') !== 'video'),

            ColorPicker::make('background_color')
                ->default('#1a1a1a'),

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
                ImageColumn::make('image')
                    ->disk('public')
                    ->visibility('public')
                    ->square()
                    ->size(48)
                    ->defaultImageUrl(url('/placeholder.png'))
                    ->url(fn ($record) => $record->image
                        ? Storage::disk('public')->url($record->image)
                        : null),

                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category'),

                TextColumn::make('year'),

                TextColumn::make('media_type')
                    ->label('Type'),

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
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
