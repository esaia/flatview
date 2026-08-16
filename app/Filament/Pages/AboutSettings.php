<?php

namespace App\Filament\Pages;

use App\Filament\Support\MediaLibrary;
use App\Models\AboutGalleryImage;
use App\Models\AboutStat;
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

class AboutSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Pages';

    protected static ?string $navigationLabel = 'About Page';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'About Page';

    protected string $view = 'filament.pages.about-settings';

    /** Setting keys backed by the HomepageSetting key/value store. */
    protected const SETTING_KEYS = [
        'about_headline',
        'about_beige_text_1',
        'about_beige_text_2',
        'about_cta_title',
        'about_cta_link_text',
        'about_valueprops_kicker',
    ];

    /** Setting keys whose value is stored as a JSON-encoded array. */
    protected const JSON_KEYS = [
        'about_valueprops',
    ];

    public ?array $data = [];

    public function mount(): void
    {
        $settings = HomepageSetting::whereIn('key', array_merge(self::SETTING_KEYS, self::JSON_KEYS))
            ->pluck('value', 'key')
            ->toArray();

        foreach (self::JSON_KEYS as $key) {
            $decoded = json_decode($settings[$key] ?? '[]', true);
            $settings[$key] = is_array($decoded) ? $decoded : [];
        }

        $settings['gallery'] = AboutGalleryImage::orderBy('sort_order')
            ->pluck('image')
            ->all();

        $settings['stats'] = AboutStat::orderBy('sort_order')
            ->get(['value', 'label'])
            ->map(fn ($stat) => ['value' => $stat->value, 'label' => $stat->label])
            ->all();

        $this->form->fill($settings);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('About')
                    ->tabs([
                        Tab::make('Text')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Textarea::make('about_headline')
                                    ->label('Headline')
                                    ->rows(2)
                                    ->required()
                                    ->helperText('e.g. "We build digital tools for the built world."'),

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
                            ]),

                        Tab::make('Why us')
                            ->icon('heroicon-o-sparkles')
                            ->schema([
                                TextInput::make('about_valueprops_kicker')
                                    ->label('Section kicker')
                                    ->placeholder('Why Flatview?'),

                                Repeater::make('about_valueprops')
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

                        Tab::make('Gallery')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                FileUpload::make('gallery')
                                    ->label('Studio photos')
                                    ->helperText('Shown as a staggered collage under the opening statement. Drag to reorder.')
                                    ->disk('public')
                                    ->directory('about-gallery')
                                    ->visibility('public')
                                    ->image()
                                    ->multiple()
                                    ->reorderable()
                                    ->appendFiles()
                                    ->panelLayout('grid')
                                    ->imagePreviewHeight('180')
                                    ->openable()
                                    ->downloadable()
                                    ->hintAction(MediaLibrary::pickerAction()),
                            ]),

                        Tab::make('Stats')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Repeater::make('stats')
                                    ->label('Stats & facts')
                                    ->schema([
                                        TextInput::make('value')
                                            ->required()
                                            ->placeholder('500+'),

                                        TextInput::make('label')
                                            ->required()
                                            ->placeholder('Projects delivered'),
                                    ])
                                    ->columns(2)
                                    ->reorderable()
                                    ->collapsible()
                                    ->defaultItems(0)
                                    ->addActionLabel('Add stat'),
                            ]),
                    ])
                    ->persistTabInQueryString(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $stats = $data['stats'] ?? [];
        $gallery = $data['gallery'] ?? [];
        unset($data['stats'], $data['gallery']);

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

        $this->syncStats($stats);
        $this->syncGallery($gallery);

        Notification::make()
            ->title('About page saved')
            ->success()
            ->send();
    }

    /**
     * Replace the gallery rows from the upload field, preserving row order.
     * Files dropped from the field are removed from disk as well.
     *
     * @param  array<int, string>  $paths
     */
    protected function syncGallery(array $paths): void
    {
        $paths = array_values(array_filter($paths));
        $previous = AboutGalleryImage::pluck('image')->all();

        foreach (array_diff($previous, $paths) as $removed) {
            Storage::disk('public')->delete($removed);
        }

        AboutGalleryImage::query()->delete();

        foreach ($paths as $index => $path) {
            AboutGalleryImage::create([
                'image' => $path,
                'sort_order' => $index,
                'is_active' => true,
            ]);
        }
    }

    /**
     * Replace about_stats rows from the repeater, preserving row order.
     *
     * @param  array<int, array{value: string, label: string}>  $stats
     */
    protected function syncStats(array $stats): void
    {
        AboutStat::query()->delete();

        $index = 0;
        foreach ($stats as $stat) {
            if (blank($stat['value'] ?? null) && blank($stat['label'] ?? null)) {
                continue;
            }

            AboutStat::create([
                'value' => $stat['value'] ?? '',
                'label' => $stat['label'] ?? '',
                'sort_order' => $index++,
                'is_active' => true,
            ]);
        }
    }
}
