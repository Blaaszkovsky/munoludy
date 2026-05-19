@php
    $title = $content['title'] ?? 'Dziękujemy!';
    $subtitle = $content['subtitle'] ?? 'Twój głos został pomyślnie zapisany.';
    $text = $content['text'] ?? 'Dziękujemy za udział w plebiscycie Munoludy. Twoje zdanie pomoże wyłonić najlepszych przedstawicieli polskiej sceny elektronicznej.';
@endphp
<x-layouts.app :title="$title">
    <x-header :title="$title" />

    <main class="flex-1 px-8 py-12 md:px-16 flex items-center justify-center">
        <div class="max-w-3xl mx-auto w-full">
            <div class="muno-card !p-8 md:!p-16 text-center">
                <div class="w-24 h-24 md:w-32 md:h-32 mx-auto mb-8 rounded-full flex items-center justify-center animate-[pulse_2s_ease-in-out_infinite]"
                     style="background-color: var(--munoludy-button-bg);">
                    <svg class="w-12 h-12 md:w-16 md:h-16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         style="color: var(--munoludy-button-text);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <h1 class="text-4xl md:text-6xl mb-6 font-heading">
                    {{ $title }}
                </h1>

                <p class="text-lg md:text-2xl mb-8 leading-relaxed font-body">
                    {{ $subtitle }}
                </p>

                <p class="text-base md:text-lg leading-relaxed font-body text-gray-600">
                    {{ $text }}
                </p>

                <div class="mt-12 flex justify-center gap-4">
                    <div class="w-16 h-16 opacity-80 animate-[bounce_3s_ease-in-out_infinite]"
                         style="background-color: var(--munoludy-button-bg);"></div>
                    <div class="w-16 h-16 opacity-80 animate-[bounce_3s_ease-in-out_infinite_0.3s]"
                         style="background-color: var(--munoludy-green);"></div>
                    <div class="w-16 h-16 opacity-80 animate-[bounce_3s_ease-in-out_infinite_0.6s]"
                         style="background-color: var(--munoludy-button-bg);"></div>
                </div>
            </div>
        </div>
    </main>

    <x-footer />

    @push('scripts')
    <script>
        const hashMatch = window.location.pathname.match(/\/glosowanie\/([a-z0-9]+)/);
        if (hashMatch) localStorage.removeItem('munoludy_draft_' + hashMatch[1]);
    </script>
    @endpush
</x-layouts.app>
