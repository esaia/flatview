@php
    $statePath = $getStatePath();
    $images = $getImages();
    $isMultiSelect = $isMultiSelect();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            multiple: @js($isMultiSelect),
            selection: $wire.$entangle('{{ $statePath }}'),
            isSelected(path) { return this.selection.includes(path) },
            toggle(path) {
                if (this.isSelected(path)) {
                    this.selection = this.selection.filter((p) => p !== path)
                } else if (this.multiple) {
                    this.selection = [...this.selection, path]
                } else {
                    this.selection = [path]
                }
            },
        }"
        class="fv-media"
    >
        @if (empty($images))
            <div class="fv-media__empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="40" height="40">
                    <rect x="3" y="3" width="18" height="18" rx="2" />
                    <circle cx="8.5" cy="8.5" r="1.5" />
                    <path d="m21 15-5-5L5 21" />
                </svg>
                <p>No images have been uploaded yet.</p>
            </div>
        @else
            <div class="fv-media__grid">
                @foreach ($images as $image)
                    @php $path = $image['path']; @endphp
                    <div
                        role="{{ $isMultiSelect ? 'checkbox' : 'radio' }}"
                        tabindex="0"
                        :aria-checked="isSelected(@js($path))"
                        @click="toggle(@js($path))"
                        @keydown.enter.prevent="toggle(@js($path))"
                        @keydown.space.prevent="toggle(@js($path))"
                        class="fv-tile"
                        :class="isSelected(@js($path)) && 'fv-tile--selected'"
                        title="{{ $image['name'] }}"
                    >
                        <img src="{{ $image['url'] }}" alt="{{ $image['name'] }}" loading="lazy" />

                        <span class="fv-tile__badge" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" width="14" height="14">
                                <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>

                        <span class="fv-tile__name">{{ $image['name'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <style>
        .fv-media__grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(132px, 1fr));
            gap: 0.85rem;
            max-height: 62vh;
            overflow-y: auto;
            padding: 0.5rem 0.5rem 0.75rem;
            scrollbar-width: thin;
        }

        .fv-tile {
            position: relative;
            aspect-ratio: 1 / 1;
            border-radius: 0.7rem;
            overflow: hidden;
            cursor: pointer;
            background: rgba(120, 120, 130, 0.08);
            box-shadow: 0 0 0 1px rgba(120, 120, 130, 0.18);
            outline: none;
            transition: transform 0.18s cubic-bezier(0.2, 0.8, 0.2, 1),
                        box-shadow 0.18s ease;
        }

        .fv-tile img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform 0.35s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        .fv-tile:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 22px -8px rgba(0, 0, 0, 0.45),
                        0 0 0 1px rgba(120, 120, 130, 0.28);
        }

        .fv-tile:hover img { transform: scale(1.05); }

        .fv-tile:focus-visible {
            box-shadow: 0 0 0 2px var(--fv-bg, #fff),
                        0 0 0 4px #3b82f6;
        }

        /* Selected state */
        .fv-tile--selected {
            box-shadow: 0 0 0 3px #3b82f6,
                        0 10px 24px -10px rgba(59, 130, 246, 0.6);
        }

        .fv-tile--selected img { transform: scale(1.04); }

        .fv-tile--selected::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(59, 130, 246, 0.12), rgba(59, 130, 246, 0.28));
            pointer-events: none;
        }

        /* Check badge */
        .fv-tile__badge {
            position: absolute;
            top: 0.45rem;
            right: 0.45rem;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 1.55rem;
            height: 1.55rem;
            border-radius: 9999px;
            color: #fff;
            background: #3b82f6;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3), 0 0 0 2px rgba(255, 255, 255, 0.85);
            opacity: 0;
            transform: scale(0.4);
            transition: opacity 0.16s ease, transform 0.2s cubic-bezier(0.2, 1.4, 0.4, 1);
        }

        .fv-tile--selected .fv-tile__badge {
            opacity: 1;
            transform: scale(1);
        }

        /* Filename label on hover */
        .fv-tile__name {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1;
            padding: 1.4rem 0.55rem 0.45rem;
            font-size: 0.68rem;
            line-height: 1.2;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            background: linear-gradient(180deg, transparent, rgba(0, 0, 0, 0.72));
            opacity: 0;
            transition: opacity 0.18s ease;
            pointer-events: none;
        }

        .fv-tile:hover .fv-tile__name,
        .fv-tile--selected .fv-tile__name { opacity: 1; }

        /* Empty state */
        .fv-media__empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
            padding: 3.5rem 1rem;
            color: rgba(120, 120, 130, 0.8);
            text-align: center;
        }
        .fv-media__empty p { margin: 0; font-size: 0.875rem; }

        .dark .fv-tile:focus-visible {
            box-shadow: 0 0 0 2px #18181b, 0 0 0 4px #3b82f6;
        }
    </style>
</x-dynamic-component>
