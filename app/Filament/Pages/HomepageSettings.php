<?php

namespace App\Filament\Pages;

use App\Models\HomepageSetting;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use IrepPlugin\FilamentIrep\Models\Project;

class HomepageSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static string|\UnitEnum|null $navigationGroup = 'Pages';

    protected static ?string $navigationLabel = 'Homepage';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Homepage';

    protected string $view = 'filament.pages.homepage-settings';

    /** Setting keys backed by the HomepageSetting key/value store. */
    protected const SETTING_KEYS = [
        'badge',
        'headline',
        'subtitle',
        'demo_project_id',
        'primary_button_label',
        'primary_button_url',
        'secondary_button_label',
        'secondary_button_url',
    ];

    public ?array $data = [];

    public function mount(): void
    {
        $settings = HomepageSetting::whereIn('key', self::SETTING_KEYS)
            ->pluck('value', 'key')
            ->toArray();

        $this->form->fill($settings);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('demo_project_id')
                    ->label('Interactive demo project')
                    ->helperText('The project shown in the interactive 360 demo on the homepage hero.')
                    ->options(fn () => Project::orderBy('title')->pluck('title', 'id'))
                    ->searchable()
                    ->required(),

                TextInput::make('badge')
                    ->label('Badge text')
                    ->required(),

                Textarea::make('headline')
                    ->label('Main headline')
                    ->rows(3)
                    ->required()
                    ->helperText('Use line breaks to control how the headline stacks.'),

                Textarea::make('subtitle')
                    ->label('Subtitle text')
                    ->rows(3)
                    ->required(),

                Section::make('Hero buttons')
                    ->description('The two call-to-action buttons shown below the subtitle.')
                    ->icon('heroicon-o-cursor-arrow-rays')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('primary_button_label')
                                    ->label('Primary button text')
                                    ->prefixIcon('heroicon-o-pencil')
                                    ->default('View Our Services')
                                    ->placeholder('View Our Services')
                                    ->required(),

                                TextInput::make('primary_button_url')
                                    ->label('Primary button link')
                                    ->prefixIcon('heroicon-o-link')
                                    ->default('/services')
                                    ->placeholder('/services')
                                    ->required(),

                                TextInput::make('secondary_button_label')
                                    ->label('Secondary button text')
                                    ->prefixIcon('heroicon-o-pencil')
                                    ->default('Get In Touch')
                                    ->placeholder('Get In Touch')
                                    ->required(),

                                TextInput::make('secondary_button_url')
                                    ->label('Secondary button link')
                                    ->prefixIcon('heroicon-o-link')
                                    ->default('/contact')
                                    ->placeholder('/contact')
                                    ->required(),
                            ]),
                    ])
                    ->collapsible(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            HomepageSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Notification::make()
            ->title('Homepage settings saved')
            ->success()
            ->send();
    }
}
