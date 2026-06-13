<?php

namespace App\Filament\Pages;

use App\Models\HomepageSetting;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

class AboutSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'About Page Text';

    protected string $view = 'filament.pages.about-settings';

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
                Textarea::make('about_headline')
                    ->label('Headline')
                    ->rows(2)
                    ->required()
                    ->helperText('e.g. "We build digital tools for the built world."'),

                TextInput::make('about_story_link')
                    ->label('Story link URL')
                    ->placeholder('#our-story'),

                Textarea::make('about_beige_text_1')
                    ->label('Beige section — paragraph 1')
                    ->rows(3)
                    ->required(),

                Textarea::make('about_beige_text_2')
                    ->label('Beige section — paragraph 2')
                    ->rows(3)
                    ->required(),

                TextInput::make('about_cta_title')
                    ->label('CTA heading')
                    ->required()
                    ->placeholder('Work with us'),

                TextInput::make('about_cta_link_text')
                    ->label('CTA link text')
                    ->required()
                    ->placeholder('Introduce yourself'),
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
            ->title('About settings saved')
            ->success()
            ->send();
    }
}
