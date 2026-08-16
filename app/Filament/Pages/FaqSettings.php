<?php

namespace App\Filament\Pages;

use App\Models\HomepageSetting;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

/**
 * One set of questions, shown on both the services and the about page.
 */
class FaqSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'FAQ';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'FAQ';

    protected string $view = 'filament.pages.faq-settings';

    /** Setting keys backed by the HomepageSetting key/value store. */
    protected const SETTING_KEYS = [
        'faq_kicker',
        'faq_headline',
        'faq_intro',
    ];

    /** Setting keys whose value is stored as a JSON-encoded array. */
    protected const JSON_KEYS = [
        'faq_items',
    ];

    public ?array $data = [];

    public function mount(): void
    {
        $stored = HomepageSetting::whereIn('key', array_merge(self::SETTING_KEYS, self::JSON_KEYS))
            ->pluck('value', 'key')
            ->toArray();

        $settings = [];

        foreach (self::SETTING_KEYS as $key) {
            $settings[$key] = $stored[$key] ?? null;
        }

        foreach (self::JSON_KEYS as $key) {
            $decoded = json_decode($stored[$key] ?? '[]', true);
            $settings[$key] = is_array($decoded) ? $decoded : [];
        }

        $this->form->fill($settings);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('faq_kicker')
                    ->label('Section kicker')
                    ->placeholder('Questions'),

                TextInput::make('faq_headline')
                    ->label('Headline')
                    ->placeholder('Answers, before you ask'),

                Textarea::make('faq_intro')
                    ->label('Intro paragraph')
                    ->rows(2)
                    ->helperText('Optional. Shown under the headline.'),

                Repeater::make('faq_items')
                    ->label('Questions')
                    ->helperText('Shown on the services page and the about page.')
                    ->schema([
                        TextInput::make('question')
                            ->required()
                            ->placeholder('How long does a project take?'),

                        Textarea::make('answer')
                            ->required()
                            ->rows(3),
                    ])
                    ->reorderable()
                    ->collapsible()
                    ->collapsed()
                    ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                    ->defaultItems(0)
                    ->addActionLabel('Add question'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach (self::JSON_KEYS as $key) {
            HomepageSetting::updateOrCreate(
                ['key' => $key],
                ['value' => json_encode(array_values($data[$key] ?? []))],
            );
            unset($data[$key]);
        }

        foreach ($data as $key => $value) {
            HomepageSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Notification::make()
            ->title('FAQ saved')
            ->success()
            ->send();
    }
}
