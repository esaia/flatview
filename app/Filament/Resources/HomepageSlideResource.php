<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HomepageSlideResource\Pages;
use App\Models\HomepageSlide;
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

class HomepageSlideResource extends Resource
{
    protected static ?string $model = HomepageSlide::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static string|\UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Homepage';

    protected static ?string $modelLabel = 'slide';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->required(),

            Select::make('media_type')
                ->options(['video' => 'Video (MP4)', 'image' => 'Image'])
                ->default('video')
                ->live(),

            FileUpload::make('video')
                ->label('Video (MP4)')
                ->disk('public')
                ->directory('homepage-slides/videos')
                ->visibility('public')
                ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/quicktime'])
                ->downloadable()
                ->openable()
                ->nullable()
                ->hidden(fn (Get $get) => $get('media_type') !== 'video'),

            FileUpload::make('image')
                ->disk('public')
                ->directory('homepage-slides')
                ->visibility('public')
                ->image()
                ->imagePreviewHeight('200')
                ->downloadable()
                ->openable()
                ->nullable()
                ->hidden(fn (Get $get) => $get('media_type') !== 'image'),

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

                TextColumn::make('media_type')
                    ->label('Type')
                    ->badge(),

                IconColumn::make('is_active')
                    ->boolean(),

                TextColumn::make('sort_order')
                    ->sortable(),
            ])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHomepageSlides::route('/'),
            'create' => Pages\CreateHomepageSlide::route('/create'),
            'edit' => Pages\EditHomepageSlide::route('/{record}/edit'),
        ];
    }
}
