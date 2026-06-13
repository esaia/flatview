<?php

namespace App\Filament\Pages;

use App\Models\HomepageSetting;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

class HomepageSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-pencil-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Homepage Text';

    protected string $view = 'filament.pages.homepage-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = HomepageSetting::pluck('value', 'key')->toArray();
        $this->form->fill($settings);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
            ->title('Settings saved')
            ->success()
            ->send();
    }
}
