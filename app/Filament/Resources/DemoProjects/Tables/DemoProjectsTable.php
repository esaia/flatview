<?php

namespace App\Filament\Resources\DemoProjects\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class DemoProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('card_image')
                    ->label('Card')
                    ->disk('public')
                    ->visibility('public')
                    ->square()
                    ->size(48)
                    ->url(fn ($record) => $record->card_image
                        ? Storage::disk('public')->url($record->card_image)
                        : null),

                TextColumn::make('title')
                    ->searchable(),

                TextColumn::make('slug'),

                // The link nulls itself when the IREP project is deleted (e.g.
                // deleted and re-imported), which otherwise fails silently: the
                // public page renders without its viewer and unit list.
                TextColumn::make('irepProject.title')
                    ->label('Interactive project')
                    ->badge()
                    ->color(fn ($state) => filled($state) ? 'gray' : 'danger')
                    ->formatStateUsing(fn ($state) => filled($state) ? $state : 'Not linked')
                    ->default('Not linked'),

                IconColumn::make('is_active')
                    ->boolean(),

                TextColumn::make('sort_order')
                    ->sortable(),
            ])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
