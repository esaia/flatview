<?php

namespace App\Filament\Pages;

use App\Models\HomepageSetting;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class ContactSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static string|\UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Contact Page';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Contact Page';

    protected string $view = 'filament.pages.contact-settings';

    /** Scalar setting keys backed by the HomepageSetting key/value store. */
    protected const SETTING_KEYS = [
        'contact_kicker',
        'contact_headline',
        'contact_form_label',
        'contact_form_subtitle',
        'contact_email_label',
        'contact_email',
        'contact_location_label',
        'contact_location',
        'contact_location_note',
        'contact_social_label',
        'contact_whatsapp_label',
        'contact_whatsapp_qr',
    ];

    /** Setting keys whose value is stored as a JSON-encoded array. */
    protected const JSON_KEYS = [
        'contact_socials',
        'contact_images',
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
                Tabs::make('Contact')
                    ->tabs([
                        Tab::make('Hero')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                TextInput::make('contact_kicker')
                                    ->label('Kicker')
                                    ->placeholder('Contact'),

                                TextInput::make('contact_headline')
                                    ->label('Headline')
                                    ->placeholder('Get in touch'),
                            ]),

                        Tab::make('Details')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                TextInput::make('contact_form_label')
                                    ->label('Form block — label')
                                    ->placeholder('Start a project'),

                                TextInput::make('contact_form_subtitle')
                                    ->label('Form block — subtitle')
                                    ->placeholder('Fill out the form below'),

                                TextInput::make('contact_email_label')
                                    ->label('Email block — label')
                                    ->placeholder('Just say hi'),

                                TextInput::make('contact_email')
                                    ->label('Email address')
                                    ->email()
                                    ->placeholder('hello@flatview.com'),

                                TextInput::make('contact_location_label')
                                    ->label('Location block — label')
                                    ->placeholder('Location'),

                                TextInput::make('contact_location')
                                    ->label('Location')
                                    ->placeholder('Tbilisi, Georgia'),

                                TextInput::make('contact_location_note')
                                    ->label('Location note')
                                    ->placeholder('Available for remote projects worldwide'),
                            ]),

                        Tab::make('Social')
                            ->icon('heroicon-o-share')
                            ->schema([
                                TextInput::make('contact_social_label')
                                    ->label('Section label')
                                    ->placeholder('Follow us'),

                                Repeater::make('contact_socials')
                                    ->label('Social links')
                                    ->schema([
                                        TextInput::make('label')
                                            ->required()
                                            ->placeholder('LinkedIn'),

                                        TextInput::make('url')
                                            ->required()
                                            ->placeholder('https://linkedin.com/...'),
                                    ])
                                    ->columns(2)
                                    ->reorderable()
                                    ->collapsible()
                                    ->defaultItems(0)
                                    ->addActionLabel('Add social link'),
                            ]),

                        Tab::make('WhatsApp')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                TextInput::make('contact_whatsapp_label')
                                    ->label('Caption')
                                    ->placeholder('Scan to chat on WhatsApp'),

                                FileUpload::make('contact_whatsapp_qr')
                                    ->label('WhatsApp QR code')
                                    ->helperText('Upload a QR code image. Leave empty to hide the WhatsApp block.')
                                    ->disk('public')
                                    ->directory('contact')
                                    ->visibility('public')
                                    ->image()
                                    ->imagePreviewHeight('160')
                                    ->openable()
                                    ->downloadable(),
                            ]),

                        Tab::make('Images')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                FileUpload::make('contact_images')
                                    ->label('Image grid')
                                    ->helperText('Shown as a grid on the left of the contact page. Drag to reorder.')
                                    ->disk('public')
                                    ->directory('contact')
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
                    ])
                    ->persistTabInQueryString(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Drop the previous images that are no longer referenced.
        $this->pruneImages($data['contact_images'] ?? []);

        // Delete the old QR code file when it is replaced or cleared.
        $this->pruneSingle('contact_whatsapp_qr', $data['contact_whatsapp_qr'] ?? null);

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
            ->title('Contact page saved')
            ->success()
            ->send();
    }

    /**
     * Delete previously-stored image files that are absent from the new set.
     *
     * @param  array<int, string>  $paths
     */
    protected function pruneImages(array $paths): void
    {
        $paths = array_values(array_filter($paths));

        $previous = json_decode(
            HomepageSetting::where('key', 'contact_images')->value('value') ?? '[]',
            true,
        );

        if (! is_array($previous)) {
            return;
        }

        foreach (array_diff($previous, $paths) as $removed) {
            Storage::disk('public')->delete($removed);
        }
    }

    /**
     * Delete the file stored for a single-file setting when its value changes.
     */
    protected function pruneSingle(string $key, ?string $new): void
    {
        $previous = HomepageSetting::where('key', $key)->value('value');

        if (filled($previous) && $previous !== $new) {
            Storage::disk('public')->delete($previous);
        }
    }
}
