<x-filament-panels::page>
    {{-- irePlugin must be defined BEFORE the Vue bundle evaluates --}}
    <script>
        window.irePlugin = @json($irePlugin);
    </script>

    @vite('resources/js/irep-admin/src/main.ts')

    <div id="irep-vue-app" style="min-height: 600px;"></div>
    <div id="irep-vue-app-responses"></div>
</x-filament-panels::page>
