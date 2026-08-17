<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 2rem;">
        <div>
            <form wire:submit="export">
                {{ $this->exportForm }}

                <div style="margin-top: 1.5rem;">
                    <x-filament::button type="submit" icon="heroicon-o-arrow-down-tray" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="export">Export</span>
                        <span wire:loading wire:target="export">Building archive…</span>
                    </x-filament::button>
                </div>
            </form>

            @if (filled($exportedFiles))
                <x-filament::section heading="Your export" icon="heroicon-o-check-circle" style="margin-top: 1.5rem;">
                    <p style="font-size: 0.875rem; margin-bottom: 0.75rem;">
                        @if (count($exportedFiles) > 1)
                            Download <strong>all {{ count($exportedFiles) }} parts</strong> — the import needs the
                            complete set.
                        @else
                            The download should have started automatically.
                        @endif
                    </p>

                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        @foreach ($exportedFiles as $file)
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.5rem 0.75rem; border-radius: 0.5rem; background: rgba(0,0,0,0.04);">
                                <span style="font-family: ui-monospace, monospace; font-size: 0.8125rem; word-break: break-all;">
                                    {{ $file['filename'] }}
                                </span>
                                <span style="display: flex; align-items: center; gap: 0.75rem; flex-shrink: 0;">
                                    <span style="font-size: 0.75rem; opacity: 0.7;">{{ $file['size'] }}</span>
                                    <x-filament::button size="xs" tag="a" href="{{ $file['url'] }}" icon="heroicon-o-arrow-down-tray">
                                        Download
                                    </x-filament::button>
                                </span>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            @endif
        </div>

        <div>
            {{ $this->importForm }}

            <div style="margin-top: 1.5rem;" wire:loading.remove wire:target="import">
                {{ $this->importAction }}
            </div>
            <div style="margin-top: 1.5rem;" wire:loading wire:target="import">
                <x-filament::button disabled>Importing — do not close this tab…</x-filament::button>
            </div>
        </div>

        <x-filament::section heading="Archives on this server"
            description="Everything in storage/app/private/sync — exports you built here and the automatic backups taken before an import. They are never deleted on their own.">
            @php($archives = $this->getArchiveSets())

            @if (filled($archives))
                <x-slot name="afterHeader">
                    {{ $this->deleteAllArchivesAction }}
                </x-slot>

                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    @foreach ($archives as $archive)
                        <div style="border-radius: 0.5rem; background: rgba(0,0,0,0.04); padding: 0.5rem 0.75rem;">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
                                <span style="font-family: ui-monospace, monospace; font-size: 0.8125rem; word-break: break-all;">
                                    {{ $archive['name'] }}
                                </span>
                                <span style="display: flex; align-items: center; gap: 0.75rem; flex-shrink: 0;">
                                    <span style="font-size: 0.75rem; opacity: 0.7;">
                                        {{ $archive['size'] }} · {{ $archive['modified'] }}
                                        @if ($archive['is_split'])
                                            · {{ count($archive['parts']) }} parts
                                        @endif
                                    </span>
                                    @unless ($archive['is_split'])
                                        <x-filament::button size="xs" color="gray" tag="a" href="{{ $archive['parts'][0]['url'] }}" icon="heroicon-o-arrow-down-tray">
                                            Download
                                        </x-filament::button>
                                    @endunless
                                    {{ ($this->deleteArchiveAction)(['archive' => $archive['name'], 'parts' => count($archive['parts'])]) }}
                                </span>
                            </div>

                            @if ($archive['is_split'])
                                <p style="font-size: 0.75rem; opacity: 0.7; margin-top: 0.5rem;">
                                    Split export — download every part to import it elsewhere.
                                </p>
                                <div style="display: flex; flex-direction: column; gap: 0.25rem; margin-top: 0.375rem;">
                                    @foreach ($archive['parts'] as $part)
                                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding-left: 0.75rem; border-left: 2px solid rgba(0,0,0,0.12);">
                                            <span style="font-family: ui-monospace, monospace; font-size: 0.75rem; word-break: break-all; opacity: 0.85;">
                                                {{ $part['filename'] }}
                                            </span>
                                            <span style="display: flex; align-items: center; gap: 0.75rem; flex-shrink: 0;">
                                                <span style="font-size: 0.75rem; opacity: 0.7;">{{ $part['size'] }}</span>
                                                <x-filament::button size="xs" color="gray" tag="a" href="{{ $part['url'] }}" icon="heroicon-o-arrow-down-tray">
                                                    Download
                                                </x-filament::button>
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p style="font-size: 0.875rem; opacity: 0.7;">No archives here yet.</p>
            @endif
        </x-filament::section>
    </div>

    <x-filament::section collapsible collapsed heading="How syncing works">
        <div style="font-size: 0.875rem; line-height: 1.6;">
            <p>
                An archive is a single <code>.zip</code> holding the selected database tables plus the
                matching uploaded images. Export it on one site, download it, then open this same page
                on the other site and upload it there.
            </p>
            <p style="margin-top: 0.75rem;">
                <strong>Replace</strong> makes the selected content on this environment identical to the
                archive: existing rows are deleted first, and IDs are preserved so images, floors and
                flats keep pointing at the right records. <strong>Merge</strong> only adds or updates
                rows by ID and leaves everything else alone.
            </p>
            <p style="margin-top: 0.75rem;">
                A full archive with images is much larger than a web server accepts in one upload, so the
                export can split it into numbered parts (<code>…zip.001</code>, <code>…zip.002</code>, …).
                Download every part, then select them all together in the import file picker — they are
                joined back into the original archive automatically. Nothing is imported unless the whole
                set is present.
            </p>
            <p style="margin-top: 0.75rem;">
                Both sites must be on the same version of the code: an archive carries content, not
                database structure. Large exports and imports can take a few minutes — leave the tab open
                until the confirmation appears.
            </p>
        </div>
    </x-filament::section>
</x-filament-panels::page>
