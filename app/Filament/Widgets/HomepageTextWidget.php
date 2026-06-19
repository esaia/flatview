<?php

namespace App\Filament\Widgets;

use App\Models\HomepageSetting;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Widgets\Widget;

class HomepageTextWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.widgets.homepage-text-widget';

    protected int|string|array $columnSpan = 'full';

    // Only render where explicitly placed (homepage slides page header),
    // not auto-registered onto the dashboard.
    protected static bool $isDiscovered = false;

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
            ->title('Homepage text saved')
            ->success()
            ->send();
    }
}
