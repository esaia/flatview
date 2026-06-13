<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AboutGalleryImageResource\Pages;
use App\Models\AboutGalleryImage;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\Storage;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AboutGalleryImageResource extends Resource
{
    protected static ?string $model = AboutGalleryImage::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static string|\UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'About Gallery';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('image')
                ->disk('public')
                ->directory('about-gallery')
                ->visibility('public')
                ->image()
                ->imagePreviewHeight('200')
                ->downloadable()
                ->openable()
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
                ImageColumn::make('image')
                    ->disk('public')
                    ->visibility('public')
                    ->square()
                    ->size(48)
                    ->url(fn ($record) => $record->image
                        ? Storage::disk('public')->url($record->image)
                        : null),

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
            'index'  => Pages\ListAboutGalleryImages::route('/'),
            'create' => Pages\CreateAboutGalleryImage::route('/create'),
            'edit'   => Pages\EditAboutGalleryImage::route('/{record}/edit'),
        ];
    }
}
