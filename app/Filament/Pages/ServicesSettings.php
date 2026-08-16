<?php

namespace App\Filament\Pages;

use App\Filament\Support\MediaLibrary;
use App\Models\DemoProject;
use App\Models\HomepageSetting;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use IrepPlugin\FilamentIrep\Models\Project as IrepProject;

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
        'services_gallery_kicker',
        'services_projects_kicker',
        'services_projects_headline',
        'services_projects_headline_accent',
        'services_projects_intro',
        'services_matters_kicker',
        'services_matters_headline',
        'services_matters_headline_accent',
        'services_matters_intro',
        'services_features_kicker',
        'services_features_headline',
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
        'services_projects_selected',
        'services_matters',
        'services_features',
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
                                TextInput::make('services_gallery_kicker')
                                    ->label('Section title')
                                    ->placeholder('Selected Work'),

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

                        Tab::make('Demo projects')
                            ->icon('heroicon-o-building-office-2')
                            ->schema([
                                TextInput::make('services_projects_kicker')
                                    ->label('Section kicker')
                                    ->placeholder('Live demos'),

                                TextInput::make('services_projects_headline')
                                    ->label('Headline')
                                    ->placeholder('See it working'),

                                TextInput::make('services_projects_headline_accent')
                                    ->label('Headline accent')
                                    ->helperText('Appended to the headline and shown in the brand green.')
                                    ->placeholder('on a real project'),

                                Textarea::make('services_projects_intro')
                                    ->label('Intro paragraph')
                                    ->rows(3),

                                Select::make('services_projects_selected')
                                    ->label('Projects to show')
                                    ->helperText('Lists the demo pages from Website → Demo Projects — one per interactive project. Leave empty to show every active page; the cards follow the order you pick them in. Use + to give another interactive project its own page.')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->options(fn () => DemoProject::orderBy('sort_order')->pluck('title', 'id'))
                                    // Quick-create so an interactive project without a demo
                                    // page can get one without leaving this tab; the rest of
                                    // its copy and imagery is edited under Demo Projects.
                                    ->createOptionForm([
                                        Select::make('project_id')
                                            ->label('Interactive project')
                                            ->options(fn () => IrepProject::whereNotIn('id', DemoProject::whereNotNull('project_id')->pluck('project_id'))
                                                ->orderBy('title')
                                                ->pluck('title', 'id'))
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                $title = IrepProject::whereKey($state)->value('title');

                                                if (filled($title)) {
                                                    $set('title', $title);
                                                    $set('slug', Str::slug($title));
                                                }
                                            })
                                            ->helperText('Projects that already have a demo page are not listed.'),

                                        TextInput::make('title')
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug((string) $state))),

                                        TextInput::make('slug')
                                            ->required()
                                            ->unique(table: DemoProject::class)
                                            ->helperText('Page URL: /projects/your-slug'),

                                        TextInput::make('tagline')
                                            ->label('Card tagline')
                                            ->placeholder('Interactive site plan · live availability'),
                                    ])
                                    ->createOptionUsing(fn (array $data) => DemoProject::create($data)->getKey()),
                            ]),

                        Tab::make('Why it matters')
                            ->icon('heroicon-o-light-bulb')
                            ->schema([
                                TextInput::make('services_matters_kicker')
                                    ->label('Section kicker')
                                    ->placeholder('Why it matters'),

                                Textarea::make('services_matters_headline')
                                    ->label('Headline')
                                    ->rows(2)
                                    ->helperText('Use a line break to control where the headline wraps.')
                                    ->placeholder("Your clients look\nfor answers"),

                                TextInput::make('services_matters_headline_accent')
                                    ->label('Headline accent')
                                    ->helperText('Appended to the headline and shown in the brand green.')
                                    ->placeholder('before they call'),

                                Textarea::make('services_matters_intro')
                                    ->label('Intro paragraph')
                                    ->rows(3),

                                Repeater::make('services_matters')
                                    ->label('Panels')
                                    ->helperText('Numbers (01, 02, …) are generated by position. Every second panel is shown inverted (dark).')
                                    ->schema([
                                        TextInput::make('title')
                                            ->required()
                                            ->placeholder('First impression'),

                                        Textarea::make('description')
                                            ->required()
                                            ->rows(3),
                                    ])
                                    ->reorderable()
                                    ->collapsible()
                                    ->collapsed()
                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                                    ->defaultItems(0)
                                    ->addActionLabel('Add panel'),
                            ]),

                        Tab::make('Showcase')
                            ->icon('heroicon-o-rectangle-group')
                            ->schema([
                                TextInput::make('services_features_kicker')
                                    ->label('Section kicker')
                                    ->placeholder('The product, shown'),

                                Textarea::make('services_features_headline')
                                    ->label('Section headline')
                                    ->rows(2)
                                    ->helperText('Use a line break to control where the headline wraps.')
                                    ->placeholder('What used to take a showroom now takes a scroll.'),

                                Repeater::make('services_features')
                                    ->label('Feature cards')
                                    ->helperText('Each card shows an image with a title and description. Two per row on desktop.')
                                    ->schema([
                                        FileUpload::make('image')
                                            ->label('Image')
                                            ->disk('public')
                                            ->directory('services-features')
                                            ->visibility('public')
                                            ->image()
                                            ->imageEditor()
                                            ->openable()
                                            ->downloadable()
                                            ->hintAction(MediaLibrary::pickerAction()),

                                        TextInput::make('title')
                                            ->required()
                                            ->placeholder('Detailed floor plan for each unit'),

                                        Textarea::make('description')
                                            ->required()
                                            ->rows(3),
                                    ])
                                    ->reorderable()
                                    ->collapsible()
                                    ->collapsed()
                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                                    ->defaultItems(0)
                                    ->addActionLabel('Add feature'),
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
