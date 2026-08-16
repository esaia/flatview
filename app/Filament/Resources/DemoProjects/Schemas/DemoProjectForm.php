<?php

namespace App\Filament\Resources\DemoProjects\Schemas;

use App\Filament\Support\MediaLibrary;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use IrepPlugin\FilamentIrep\Models\Project as IrepProject;

class DemoProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Project')
                    ->schema([
                        Select::make('project_id')
                            ->label('Interactive project')
                            ->helperText('The IREP project whose site plan and units are shown on the page.')
                            ->options(fn () => IrepProject::orderBy('title')->pluck('title', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('title')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, callable $set) {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug((string) $state));
                                }
                            })
                            ->placeholder('Small Developments'),

                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Used in the page URL: /projects/your-slug'),

                        TextInput::make('tagline')
                            ->label('Card tagline')
                            ->helperText('One line shown under the title in the services page list.')
                            ->placeholder('Interactive site plan · 24 units'),

                        FileUpload::make('card_image')
                            ->label('Card image')
                            ->disk('public')
                            ->directory('demo-projects')
                            ->visibility('public')
                            ->image()
                            ->imageEditor()
                            ->openable()
                            ->downloadable()
                            ->hintAction(MediaLibrary::pickerAction()),
                    ])
                    ->columns(2),

                Section::make('Hero')
                    ->schema([
                        FileUpload::make('hero_image')
                            ->label('Hero background')
                            ->helperText('Full-width image behind the project title. Dark images work best.')
                            ->disk('public')
                            ->directory('demo-projects')
                            ->visibility('public')
                            ->image()
                            ->imageEditor()
                            ->openable()
                            ->downloadable()
                            ->hintAction(MediaLibrary::pickerAction()),

                        Textarea::make('hero_description')
                            ->label('Hero paragraph')
                            ->rows(3),
                    ]),

                Section::make('Site plan section')
                    ->description('Introduces the interactive viewer.')
                    ->schema([
                        TextInput::make('plan_kicker')
                            ->label('Kicker')
                            ->placeholder('Interactive presentation'),

                        TextInput::make('plan_headline')
                            ->label('Headline')
                            ->placeholder('Click on a unit and'),

                        TextInput::make('plan_headline_accent')
                            ->label('Headline accent')
                            ->helperText('Appended to the headline and shown in the brand green.')
                            ->placeholder('check the details'),

                        Textarea::make('plan_intro')
                            ->label('Intro paragraph')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('Units section')
                    ->description('Introduces the filterable list of units.')
                    ->schema([
                        TextInput::make('units_kicker')
                            ->label('Kicker')
                            ->placeholder('Available units'),

                        TextInput::make('units_headline')
                            ->label('Headline')
                            ->placeholder('Find a unit that'),

                        TextInput::make('units_headline_accent')
                            ->label('Headline accent')
                            ->placeholder('matches your requirements'),

                        Textarea::make('units_intro')
                            ->label('Intro paragraph')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('Publishing')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),

                        TextInput::make('sort_order')
                            ->label('Order')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(2),
            ]);
    }
}
