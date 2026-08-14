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
