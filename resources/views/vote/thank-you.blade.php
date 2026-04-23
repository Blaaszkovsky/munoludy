<x-layouts.app title="Dziękujemy">
    <x-header title="{{ $content['title'] }}" />
    <main class="flex-1 px-8 py-12 md:px-16 flex items-center">
        <div class="max-w-3xl mx-auto">
            <x-form-card class="text-center">
                <p class="text-white/90 text-lg font-body">{{ $content['text'] }}</p>
            </x-form-card>
        </div>
    </main>
    <x-footer />
    @push('scripts')
    <script>
        const hashMatch = window.location.pathname.match(/\/glosowanie\/([a-z0-9]+)/);
        if (hashMatch) localStorage.removeItem('munoludy_draft_'+hashMatch[1]);
    </script>
    @endpush
</x-layouts.app>
