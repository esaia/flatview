<?php

namespace App\Filament\Pages;

use App\Models\HomepageSetting;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use IrepPlugin\FilamentIrep\Models\Project;

class HomepageSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static string|\UnitEnum|null $navigationGroup = 'Website';

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
