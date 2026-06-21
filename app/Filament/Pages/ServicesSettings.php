<?php

namespace App\Filament\Pages;

use App\Filament\Support\MediaLibrary;
use App\Models\HomepageSetting;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class ServicesSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string|\UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Services Page';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Services Page';

    protected string $view = 'filament.pages.services-settings';

    /** Scalar setting keys backed by the HomepageSetting key/value store. */
    protected const SETTING_KEYS = [
        'services_kicker',
        'services_headline',
        'services_intro',
        'services_valueprops_kicker',
        'services_process_kicker',
        'services_process_headline',
        'services_process_intro',
        'services_cta_kicker',
        'services_cta_headline',
        'services_cta_button_text',
        'services_cta_button_link',
    ];

    /** Setting keys whose value is stored as a JSON-encoded array. */
    protected const JSON_KEYS = [
        'services_blocks',
        'services_valueprops',
        'services_process',
        'services_gallery',
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
                Tabs::make('Services')
                    ->tabs([
                        Tab::make('Hero')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                TextInput::make('services_kicker')
                                    ->label('Kicker')
                                    ->placeholder('Our Services'),

                                Textarea::make('services_headline')
                                    ->label('Headline')
                                    ->rows(2)
                                    ->placeholder('What we do'),

                                Textarea::make('services_intro')
                                    ->label('Intro paragraph')
                                    ->rows(3),
                            ]),

                        Tab::make('Gallery')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                FileUpload::make('services_gallery')
                                    ->label('Selected work images')
                                    ->helperText('Drop multiple images at once and drag to reorder.')
                                    ->disk('public')
                                    ->directory('services-gallery')
                                    ->visibility('public')
                                    ->image()
                                    ->multiple()
                                    ->reorderable()
                                    ->appendFiles()
                                    ->panelLayout('grid')
                                    ->itemPanelAspectRatio('9:16')
                                    ->imagePreviewHeight('220')
                                    ->openable()
                                    ->downloadable()
                                    ->hintAction(MediaLibrary::pickerAction()),
                            ]),

                        Tab::make('Services')
                            ->icon('heroicon-o-squares-2x2')
                            ->schema([
                                Repeater::make('services_blocks')
                                    ->label('Service blocks')
                                    ->helperText('Numbers (01, 02, …) are generated automatically by position.')
                                    ->schema([
                                        TextInput::make('name')
                                            ->required()
                                            ->placeholder('Website development'),

                                        Textarea::make('description')
                                            ->required()
                                            ->rows(3),
                                    ])
                                    ->reorderable()
                                    ->collapsible()
                                    ->collapsed()
                                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                    ->defaultItems(0)
                                    ->addActionLabel('Add service'),
                            ]),

                        Tab::make('Why us')
                            ->icon('heroicon-o-sparkles')
                            ->schema([
                                TextInput::make('services_valueprops_kicker')
                                    ->label('Section kicker')
                                    ->placeholder('Why Flatview?'),

                                Repeater::make('services_valueprops')
                                    ->label('Value props')
                                    ->schema([
                                        TextInput::make('label')
                                            ->required()
                                            ->placeholder('Niche focused'),

                                        TextInput::make('detail')
                                            ->required()
                                            ->placeholder('We work exclusively in construction and real estate.'),
                                    ])
                                    ->columns(2)
                                    ->reorderable()
                                    ->collapsible()
                                    ->defaultItems(0)
                                    ->addActionLabel('Add value prop'),
                            ]),

                        Tab::make('Process')
                            ->icon('heroicon-o-list-bullet')
                            ->schema([
                                TextInput::make('services_process_kicker')
                                    ->label('Kicker')
                                    ->placeholder('How we work'),

                                Textarea::make('services_process_headline')
                                    ->label('Headline')
                                    ->rows(2)
                                    ->placeholder('A clear path from first call to launch day.'),

                                Textarea::make('services_process_intro')
                                    ->label('Supporting line')
                                    ->rows(2),

                                Repeater::make('services_process')
                                    ->label('Steps')
                                    ->helperText('Step numbers (01, 02, …) are generated automatically by position.')
                                    ->schema([
                                        TextInput::make('title')
                                            ->required()
                                            ->placeholder('Discovery'),

                                        Textarea::make('detail')
                                            ->required()
                                            ->rows(2),
                                    ])
                                    ->reorderable()
                                    ->collapsible()
                                    ->collapsed()
                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                                    ->defaultItems(0)
                                    ->addActionLabel('Add step'),
                            ]),

                        Tab::make('CTA')
                            ->icon('heroicon-o-megaphone')
                            ->schema([
                                TextInput::make('services_cta_kicker')
                                    ->label('Kicker')
                                    ->placeholder("Let's talk"),

                                Textarea::make('services_cta_headline')
                                    ->label('Headline')
                                    ->rows(2)
                                    ->helperText('Use a line break to control where the headline wraps.')
                                    ->placeholder("Let's make real estate digitally perfect."),

                                TextInput::make('services_cta_button_text')
                                    ->label('Button text')
                                    ->placeholder('Schedule a meeting'),

                                TextInput::make('services_cta_button_link')
                                    ->label('Button link URL')
                                    ->placeholder('/contact'),
                            ]),
                    ])
                    ->persistTabInQueryString(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Drop the previous gallery images that are no longer referenced.
        $this->pruneGallery($data['services_gallery'] ?? []);

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
            ->title('Services page saved')
            ->success()
            ->send();
    }

    /**
     * Delete previously-stored gallery files that are absent from the new set.
     *
     * @param  array<int, string>  $paths
     */
    protected function pruneGallery(array $paths): void
    {
        $paths = array_values(array_filter($paths));

        $previous = json_decode(
            HomepageSetting::where('key', 'services_gallery')->value('value') ?? '[]',
            true,
        );

        if (! is_array($previous)) {
            return;
        }

        foreach (array_diff($previous, $paths) as $removed) {
            Storage::disk('public')->delete($removed);
        }
    }
}
