<?php

namespace App\Filament\Pages;

use App\Models\AboutGalleryImage;
use App\Models\AboutStat;
use App\Models\HomepageSetting;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class AboutSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'About Page';

    protected static ?string $title = 'About Page';

    protected string $view = 'filament.pages.about-settings';

    /** Setting keys backed by the HomepageSetting key/value store. */
    protected const SETTING_KEYS = [
        'about_headline',
        'about_story_link',
        'about_beige_text_1',
        'about_beige_text_2',
        'about_cta_title',
        'about_cta_link_text',
    ];

    public ?array $data = [];

    public function mount(): void
    {
        $settings = HomepageSetting::whereIn('key', self::SETTING_KEYS)
            ->pluck('value', 'key')
            ->toArray();

        $settings['gallery'] = AboutGalleryImage::orderBy('sort_order')->pluck('image')->all();

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
                            ]),

                        Tab::make('Gallery')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                FileUpload::make('gallery')
                                    ->label('Gallery images')
                                    ->helperText('Drop multiple images at once and drag to reorder.')
                                    ->disk('public')
                                    ->directory('about-gallery')
                                    ->visibility('public')
                                    ->image()
                                    ->multiple()
                                    ->reorderable()
                                    ->appendFiles()
                                    ->panelLayout('grid')
                                    ->imagePreviewHeight('120')
                                    ->openable()
                                    ->downloadable(),
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

        $gallery = $data['gallery'] ?? [];
        $stats = $data['stats'] ?? [];
        unset($data['gallery'], $data['stats']);

        foreach ($data as $key => $value) {
            HomepageSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->syncGallery($gallery);
        $this->syncStats($stats);

        Notification::make()
            ->title('About page saved')
            ->success()
            ->send();
    }

    /**
     * Mirror the uploaded gallery paths into about_gallery_images, keeping
     * upload order as sort_order and removing any images no longer present.
     *
     * @param  array<int, string>  $paths
     */
    protected function syncGallery(array $paths): void
    {
        $paths = array_values(array_filter($paths));

        $removed = AboutGalleryImage::whereNotIn('image', $paths)->pluck('image');
        foreach ($removed as $path) {
            Storage::disk('public')->delete($path);
        }
        AboutGalleryImage::whereNotIn('image', $paths)->delete();

        foreach ($paths as $index => $path) {
            AboutGalleryImage::updateOrCreate(
                ['image' => $path],
                ['sort_order' => $index, 'is_active' => true],
            );
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
