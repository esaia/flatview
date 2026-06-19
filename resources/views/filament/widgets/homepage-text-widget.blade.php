<x-filament-widgets::widget>
    <x-filament::section
        heading="Homepage Text"
        description="Headline, subtitle and badge shown on the homepage hero."
    >
        <form wire:submit="save">
            {{ $this->form }}

            <div class="mt-6">
                <x-filament::button type="submit">
                    Save homepage text
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-widgets::widget>
