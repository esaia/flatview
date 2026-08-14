<?php

namespace App\Filament\Pages;

use App\Support\SiteSync\ArchiveParts;
use App\Support\SiteSync\SiteExporter;
use App\Support\SiteSync\SiteImporter;
use App\Support\SiteSync\SyncManifest;
use App\Support\SiteSync\SyncStorage;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Move content between environments without touching the command line: export
 * what is here into a .zip, or restore a .zip taken from another environment.
 *
 * Archives with images are far larger than a browser upload allows, so exports
 * can be split into numbered parts that are uploaded one at a time and
 * reassembled on the target. See {@see ArchiveParts}.
 */
class SiteSync extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string|\UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Export / Import';

    protected static ?int $navigationSort = 20;

    protected static ?string $title = 'Export / Import content';

    protected string $view = 'filament.pages.site-sync';

    public ?array $exportData = [];

    public ?array $importData = [];

    /**
     * Files produced by the last export in this browser session, so the page can
     * offer a download link per part.
     *
     * @var array<int, array{filename: string, size: string, url: string}>
     */
    public array $exportedFiles = [];

    public function mount(): void
    {
        $this->exportForm->fill([
            'groups' => SyncManifest::defaultGroupKeys(),
            'include_files' => true,
            'part_size' => 32,
        ]);

        $this->importForm->fill([
            'source' => 'upload',
            'groups' => SyncManifest::groupKeys(),
            'mode' => SiteImporter::MODE_REPLACE,
            'include_files' => true,
            'prune_files' => false,
            'backup' => true,
        ]);
    }

    public function exportForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('exportData')
            ->components([
                Section::make('What to export')
                    ->description('Builds a .zip you can download here and upload on the other environment.')
                    ->schema([
                        CheckboxList::make('groups')
                            ->label('Content')
                            ->options($this->groupOptions())
                            ->descriptions($this->groupDescriptions())
                            ->columns(2)
                            ->required(),
                        Toggle::make('include_files')
                            ->label('Include uploaded images')
                            ->live()
                            ->helperText('Off produces a small database-only archive — useful when the images are already the same on both sides.'),
                        Select::make('part_size')
                            ->label('Split into parts of')
                            ->options([
                                0 => 'Do not split — one single file',
                                8 => '8 MB per part',
                                16 => '16 MB per part',
                                32 => '32 MB per part (safe on most hosts)',
                                64 => '64 MB per part',
                                128 => '128 MB per part',
                            ])
                            ->selectablePlaceholder(false)
                            ->visible(fn (Get $get) => (bool) $get('include_files'))
                            ->helperText(sprintf(
                                'The import form can only accept files up to this server\'s upload limit (%s), and other servers are often lower. Splitting keeps every part uploadable; you select them all at once when importing.',
                                SyncStorage::humanSize($this->maxUploadBytes()),
                            )),
                    ]),
            ]);
    }

    public function importForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('importData')
            ->components([
                Section::make('Restore an archive')
                    ->description('Reads a .zip created by the export above, on this or another environment.')
                    ->schema([
                        Radio::make('source')
                            ->label('Where is the archive?')
                            ->options([
                                'upload' => 'Upload it from my computer',
                                'server' => 'It is already in storage/app/private/sync on this server',
                            ])
                            ->live()
                            ->required(),
                        FileUpload::make('archive')
                            ->label('Archive file(s)')
                            ->multiple()
                            ->disk('local')
                            ->directory(SyncStorage::DIRECTORY)
                            ->visibility('private')
                            ->preserveFilenames()
                            ->maxSize((int) ($this->maxUploadBytes() / 1024))
                            ->helperText(sprintf(
                                'Single .zip, or every part of a split export at once (…zip.001, …zip.002, …). Each file must be %s or smaller — re-export with a smaller part size if one is too big.',
                                SyncStorage::humanSize($this->maxUploadBytes()),
                            ))
                            ->visible(fn (Get $get) => $get('source') === 'upload')
                            ->required(fn (Get $get) => $get('source') === 'upload'),
                        Select::make('server_files')
                            ->label('Archive on this server')
                            ->multiple()
                            ->options(fn () => SyncStorage::available())
                            ->searchable()
                            ->helperText('Pick the single .zip, or all parts of a split export.')
                            ->visible(fn (Get $get) => $get('source') === 'server')
                            ->required(fn (Get $get) => $get('source') === 'server'),
                        CheckboxList::make('groups')
                            ->label('Restore which content')
                            ->options($this->groupOptions())
                            ->descriptions($this->groupDescriptions())
                            ->columns(2)
                            ->helperText('Anything selected here that the archive does not contain is simply skipped.')
                            ->required(),
                        Radio::make('mode')
                            ->label('Mode')
                            ->options([
                                SiteImporter::MODE_REPLACE => 'Replace — the selected content ends up exactly as in the archive (existing rows are deleted first)',
                                SiteImporter::MODE_MERGE => 'Merge — rows are added or updated by ID, and anything only present here is kept',
                            ])
                            ->required(),
                        Toggle::make('include_files')
                            ->label('Restore images from the archive'),
                        Toggle::make('prune_files')
                            ->label('Delete images that are not in the archive')
                            ->helperText('Makes the image folders an exact mirror. Leave off unless you are sure — deleted files cannot be recovered from here.'),
                        Toggle::make('backup')
                            ->label('Back up current database content first')
                            ->helperText('Writes a database-only archive to storage/app/private/sync before touching anything.'),
                    ]),
            ]);
    }

    public function export(): void
    {
        $data = $this->exportForm->getState();
        $this->exportedFiles = [];

        $this->allowLongRunningRequest();

        $includeFiles = (bool) ($data['include_files'] ?? true);
        $partSize = (int) ($data['part_size'] ?? 0);

        try {
            $result = app(SiteExporter::class)->export(
                $data['groups'],
                includeFiles: $includeFiles,
                partSize: ($includeFiles && $partSize > 0) ? $partSize * 1024 * 1024 : null,
            );
        } catch (Throwable $exception) {
            $this->notifyFailure('Export failed', $exception);

            return;
        }

        $this->exportedFiles = array_map(fn (array $file) => [
            'filename' => $file['filename'],
            'size' => SyncStorage::humanSize($file['bytes']),
            'url' => route('admin.sync.download', ['archive' => $file['filename']]),
        ], $result['files']);

        Notification::make()
            ->title('Export ready')
            ->body(sprintf(
                '%s rows and %s images, in %s. Download links are below the form.',
                number_format(array_sum($result['manifest']['tables'])),
                number_format($result['manifest']['file_count']),
                count($this->exportedFiles) === 1
                    ? 'one file ('.$this->exportedFiles[0]['size'].')'
                    : count($this->exportedFiles).' parts',
            ))
            ->success()
            ->persistent()
            ->send();

        // One file can start downloading straight away; parts are clicked
        // individually so the browser does not block the extra downloads.
        if (count($this->exportedFiles) === 1) {
            $this->js('window.location.href = '.json_encode($this->exportedFiles[0]['url']).';');
        }
    }

    public function importAction(): Action
    {
        return Action::make('import')
            ->label('Import archive')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Import content')
            ->modalDescription('This overwrites content on this environment. Make sure you picked the right archive.')
            ->modalSubmitActionLabel('Yes, import')
            ->action(fn () => $this->import());
    }

    public function import(): void
    {
        $data = $this->importForm->getState();

        $parts = $data['source'] === 'server'
            ? array_values($data['server_files'] ?? [])
            : $this->uploadedPaths($data['archive'] ?? null);

        $parts = array_values(array_filter($parts, 'is_readable'));

        if ($parts === []) {
            Notification::make()
                ->title('Archive not found')
                ->body('Choose the .zip file — or every part of a split export — to import.')
                ->danger()
                ->send();

            return;
        }

        $this->allowLongRunningRequest();

        try {
            $result = app(SiteImporter::class)->importParts(
                $parts,
                groups: $data['groups'],
                mode: $data['mode'],
                includeFiles: (bool) ($data['include_files'] ?? true),
                pruneFiles: (bool) ($data['prune_files'] ?? false),
                backup: (bool) ($data['backup'] ?? true),
            );
        } catch (Throwable $exception) {
            $this->notifyFailure('Import failed', $exception);

            return;
        }

        $body = sprintf(
            '%s rows restored across %s tables. %s images written%s.',
            number_format(array_sum($result['tables'])),
            count($result['tables']),
            number_format($result['files']),
            $result['pruned'] > 0 ? ', '.number_format($result['pruned']).' removed' : '',
        );

        foreach ($result['skipped_columns'] as $table => $columns) {
            $body .= sprintf(' Skipped unknown columns on %s: %s.', $table, implode(', ', $columns));
        }

        Notification::make()
            ->title('Import complete')
            ->body($body)
            ->success()
            ->persistent()
            ->send();
    }

    /**
     * Filament stores each upload as a disk-relative path keyed by a random id.
     *
     * @return array<int, string>
     */
    private function uploadedPaths(mixed $state): array
    {
        $paths = array_values(array_filter((array) $state, 'filled'));

        return ArchiveParts::sort(array_map(
            fn (string $relative) => Storage::disk('local')->path($relative),
            $paths,
        ));
    }

    /** Zipping or restoring 100k images outlasts the default request limits. */
    private function allowLongRunningRequest(): void
    {
        @set_time_limit(0);
        ignore_user_abort(true);
    }

    private function maxUploadBytes(): int
    {
        $limits = array_filter([
            $this->iniBytes('upload_max_filesize'),
            $this->iniBytes('post_max_size'),
        ]);

        return $limits === [] ? 64 * 1024 * 1024 : (int) min($limits);
    }

    private function iniBytes(string $key): int
    {
        $value = trim((string) ini_get($key));

        if ($value === '') {
            return 0;
        }

        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }

    private function notifyFailure(string $title, Throwable $exception): void
    {
        report($exception);

        Notification::make()
            ->title($title)
            ->body($exception->getMessage())
            ->danger()
            ->persistent()
            ->send();
    }

    /** @return array<string, string> */
    private function groupOptions(): array
    {
        return array_map(fn (array $group) => $group['label'], SyncManifest::groups());
    }

    /** @return array<string, string> */
    private function groupDescriptions(): array
    {
        return array_map(fn (array $group) => $group['description'], SyncManifest::groups());
    }
}
