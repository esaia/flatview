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

class WorkSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static string|\UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Work Page';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Work Page';

    protected string $view = 'filament.pages.work-settings';

    /** Scalar setting keys backed by the HomepageSetting key/value store. */
    protected const SETTING_KEYS = [
        'work_kicker',
        'work_headline',
        'work_intro',
    ];

    /** Setting keys whose value is stored as a JSON-encoded array. */
    protected const JSON_KEYS = [
        'work_projects',
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
                Tabs::make('Work')
                    ->tabs([
                        Tab::make('Header')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                TextInput::make('work_kicker')
                                    ->label('Kicker')
                                    ->placeholder('Portfolio'),

                                TextInput::make('work_headline')
                                    ->label('Headline')
                                    ->placeholder('Selected Work'),

                                Textarea::make('work_intro')
                                    ->label('Intro paragraph')
                                    ->rows(3)
                                    ->placeholder('Real estate platforms, interactive floor-plan tooling and the studio work behind them.'),
                            ]),

                        Tab::make('Projects')
                            ->icon('heroicon-o-squares-2x2')
                            ->schema([
                                Repeater::make('work_projects')
                                    ->label('Projects')
                                    ->helperText('Drag to reorder. Numbers (01, 02, …) are generated automatically by position.')
                                    ->schema([
                                        TextInput::make('name')
                                            ->required()
                                            ->placeholder('Tbilisi Towers'),

                                        TextInput::make('desc')
                                            ->label('Description')
                                            ->required()
                                            ->placeholder('Real estate website'),

                                        TextInput::make('link')
                                            ->label('Project URL')
                                            ->url()
                                            ->helperText('Where the project card links to. Leave empty for no link.')
                                            ->placeholder('https://example.com'),

                                        FileUpload::make('image')
                                            ->label('Image (optional)')
                                            ->helperText('Leave empty to use a generated gradient placeholder.')
                                            ->disk('public')
                                            ->directory('work')
                                            ->visibility('public')
                                            ->image()
                                            ->imagePreviewHeight('140')
                                            ->openable()
                                            ->downloadable()
                                            ->hintAction(MediaLibrary::pickerAction()),
                                    ])
                                    ->columns(2)
                                    ->reorderable()
                                    ->collapsible()
                                    ->collapsed()
                                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                    ->defaultItems(0)
                                    ->addActionLabel('Add project'),
                            ]),
                    ])
                    ->persistTabInQueryString(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Drop project images that are no longer referenced.
        $this->pruneImages($data['work_projects'] ?? []);

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
            ->title('Work page saved')
            ->success()
            ->send();
    }

    /**
     * Delete previously-stored project images that are absent from the new set.
     *
     * @param  array<int, array<string, mixed>>  $projects
     */
    protected function pruneImages(array $projects): void
    {
        $current = array_values(array_filter(array_map(
            fn ($project) => $project['image'] ?? null,
            $projects,
        )));

        $previous = json_decode(
            HomepageSetting::where('key', 'work_projects')->value('value') ?? '[]',
            true,
        );

        if (! is_array($previous)) {
            return;
        }

        $previousImages = array_values(array_filter(array_map(
            fn ($project) => is_array($project) ? ($project['image'] ?? null) : null,
            $previous,
        )));

        foreach (array_diff($previousImages, $current) as $removed) {
            Storage::disk('public')->delete($removed);
        }
    }
}
