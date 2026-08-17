<?php

namespace App\Filament\Resources\Services\Schemas;

use App\Filament\Support\MediaLibrary;
use App\Models\DemoProject;
use App\Support\RichText;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, $state, callable $set) {
                        if ($operation === 'create') {
                            $set('slug', Str::slug($state));
                        }
                    })
                    ->placeholder('Website development'),

                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->helperText('Used in the page URL: /services/your-slug. Or paste a full URL (https://…) to link the card straight to an external page instead.'),

                Textarea::make('description')
                    ->label('Card description')
                    ->required()
                    ->rows(3)
                    ->helperText('Shown on the /services overview card.'),

                FileUpload::make('hero_image')
                    ->label('Hero image')
                    ->disk('public')
                    ->directory('services')
                    ->visibility('public')
                    ->image()
                    ->imageEditor()
                    ->openable()
                    ->downloadable()
                    ->hintAction(MediaLibrary::pickerAction())
                    ->nullable(),

                FileUpload::make('hover_image')
                    ->label('Card hover image')
                    ->helperText('Revealed behind the card on the /services overview when it is hovered.')
                    ->disk('public')
                    ->directory('services')
                    ->visibility('public')
                    ->image()
                    ->imageEditor()
                    ->openable()
                    ->downloadable()
                    ->hintAction(MediaLibrary::pickerAction())
                    ->nullable(),

                FileUpload::make('cta_background')
                    ->label('Closing CTA background image')
                    ->helperText('Sits behind the closing "Let\'s talk" block on this page, darkened so the white text stays readable. Leave empty to use the shared image from Services Page → CTA.')
                    ->disk('public')
                    ->directory('services')
                    ->visibility('public')
                    ->image()
                    ->imageEditor()
                    ->openable()
                    ->downloadable()
                    ->hintAction(MediaLibrary::pickerAction())
                    ->nullable(),

                Builder::make('content_blocks')
                    ->label('Page content')
                    ->helperText('Build the service page out of sections, in any order and mix.')
                    ->blocks([
                        Block::make('rich_text')
                            ->label('Text')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                RichEditor::make('content')
                                    ->label('Content')
                                    ->required(),
                            ]),

                        Block::make('image_text')
                            ->label('Image + text')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                FileUpload::make('image')
                                    ->label('Image')
                                    ->disk('public')
                                    ->directory('services')
                                    ->visibility('public')
                                    ->image()
                                    ->imageEditor()
                                    ->openable()
                                    ->downloadable()
                                    ->hintAction(MediaLibrary::pickerAction())
                                    ->required(),

                                TextInput::make('heading')
                                    ->required(),

                                // Was a plain textarea, so older blocks hold raw
                                // text; it is promoted to HTML on the way in.
                                RichEditor::make('text')
                                    ->formatStateUsing(fn (?string $state): ?string => RichText::fromPlain($state))
                                    ->required(),

                                Select::make('image_position')
                                    ->label('Image position')
                                    ->options(['left' => 'Left', 'right' => 'Right'])
                                    ->default('left')
                                    ->required(),

                                // Optional call to action under the text. The
                                // link is only demanded once a label is given,
                                // so a block without a button stays valid.
                                TextInput::make('button_label')
                                    ->label('Button text')
                                    ->placeholder('Get in touch')
                                    ->helperText('Optional — leave empty for no button.'),

                                TextInput::make('button_url')
                                    ->label('Button link')
                                    ->placeholder('/contact')
                                    ->required(fn (Get $get): bool => filled($get('button_label'))),

                                Select::make('button_style')
                                    ->label('Button style')
                                    ->options(['solid' => 'Solid', 'outline' => 'Outline'])
                                    ->default('solid'),

                                Toggle::make('button_new_tab')
                                    ->label('Open in a new tab')
                                    ->default(false),
                            ]),

                        // The same "live demos" section the services overview
                        // shows. Only the picked ids are stored; the cards are
                        // built when the page is served.
                        Block::make('demo_projects')
                            ->label('Live demo projects')
                            ->icon('heroicon-o-building-office-2')
                            ->schema([
                                TextInput::make('kicker')
                                    ->label('Section kicker')
                                    ->default('Live demos'),

                                TextInput::make('headline')
                                    ->placeholder('Try it the way'),

                                TextInput::make('headline_accent')
                                    ->label('Headline accent')
                                    ->helperText('Continues the headline in the brand green.')
                                    ->placeholder('your buyers will'),

                                Textarea::make('intro')
                                    ->rows(3)
                                    ->helperText('Optional paragraph under the headline.'),

                                Select::make('project_ids')
                                    ->label('Projects')
                                    ->helperText('Leave empty to show every active demo project.')
                                    ->multiple()
                                    ->options(fn (): array => DemoProject::where('is_active', true)
                                        ->orderBy('sort_order')
                                        ->pluck('title', 'id')
                                        ->all()),
                            ]),

                        Block::make('quote')
                            ->label('Quote')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                Textarea::make('quote')
                                    ->rows(3)
                                    ->required(),

                                TextInput::make('attribution')
                                    ->helperText('e.g. a name and role, or leave blank.'),
                            ]),

                        Block::make('stats')
                            ->label('Stats row')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Repeater::make('items')
                                    ->schema([
                                        TextInput::make('value')
                                            ->required()
                                            ->placeholder('98%'),

                                        TextInput::make('label')
                                            ->required()
                                            ->placeholder('Client retention'),
                                    ])
                                    ->columns(2)
                                    ->reorderable()
                                    ->defaultItems(2)
                                    ->addActionLabel('Add stat'),
                            ]),

                        Block::make('button')
                            ->label('Button')
                            ->icon('heroicon-o-cursor-arrow-rays')
                            ->schema([
                                TextInput::make('label')
                                    ->required()
                                    ->placeholder('Get in touch'),

                                TextInput::make('url')
                                    ->label('Link')
                                    ->required()
                                    ->placeholder('/contact'),

                                Select::make('style')
                                    ->options(['solid' => 'Solid', 'outline' => 'Outline'])
                                    ->default('solid')
                                    ->required(),

                                Select::make('alignment')
                                    ->options(['left' => 'Left', 'center' => 'Center'])
                                    ->default('left')
                                    ->required(),

                                Toggle::make('new_tab')
                                    ->label('Open in a new tab')
                                    ->default(false),
                            ]),

                        Block::make('feature_list')
                            ->label('Feature list')
                            ->icon('heroicon-o-check-circle')
                            ->schema([
                                TextInput::make('heading')
                                    ->default("What's included"),

                                Repeater::make('items')
                                    ->schema([
                                        TextInput::make('title')
                                            ->required()
                                            ->placeholder('Custom design'),

                                        Textarea::make('detail')
                                            ->rows(2)
                                            ->required(),
                                    ])
                                    ->reorderable()
                                    ->collapsible()
                                    ->collapsed()
                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                                    ->defaultItems(0)
                                    ->addActionLabel('Add feature'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->blockNumbers(false)
                    ->addActionLabel('Add section')
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),

                TextInput::make('sort_order')
                    ->numeric()
                    ->label('Order')
                    ->default(0),
            ]);
    }
}
