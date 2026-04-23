@php
    $title = $content['title'] ?? 'Dziękujemy!';
    $subtitle = $content['subtitle'] ?? 'Twój głos został pomyślnie zapisany.';
    $text = $content['text'] ?? 'Dziękujemy za udział w plebiscycie Munoludy. Twoje zdanie pomoże wyłonić najlepszych przedstawicieli polskiej sceny elektronicznej.';
@endphp
<x-layouts.app :title="$title">
    <header class="px-8 py-8 md:px-16 md:py-12">
        <div class="max-w-6xl mx-auto flex items-center justify-center">
            <div class="flex items-center gap-4">
                <img src="{{ asset('images/muno-logo.svg') }}" alt="Muno.pl" class="h-6 md:h-8">
                <span class="text-white/60 text-sm md:text-base font-body">&amp;</span>
                <img src="{{ asset('images/biletomat-logo-dark.svg') }}" alt="Biletomat" class="h-6 md:h-8">
            </div>
        </div>
    </header>

    <main class="flex-1 px-8 py-12 md:px-16 flex items-center justify-center">
        <div class="max-w-3xl mx-auto w-full">
            <div class="bg-white/10 backdrop-blur-md rounded-3xl p-8 md:p-16 border border-white/20 shadow-2xl text-center">
                <div class="w-24 h-24 md:w-32 md:h-32 mx-auto mb-8 rounded-full flex items-center justify-center animate-[pulse_2s_ease-in-out_infinite]"
                     style="background-color: var(--munoludy-button-bg);">
                    <svg class="w-12 h-12 md:w-16 md:h-16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         style="color: var(--munoludy-button-text);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <h1 class="text-4xl md:text-6xl mb-6 font-heading" style="color: var(--munoludy-text);">
                    {{ $title }}
                </h1>

                <p class="text-white/80 text-lg md:text-2xl mb-8 leading-relaxed font-body">
                    {{ $subtitle }}
                </p>

                <p class="text-white/60 text-base md:text-lg leading-relaxed font-body">
                    {{ $text }}
                </p>

                <div class="mt-12 flex justify-center gap-4">
                    <div class="w-16 h-16 rounded-full opacity-60 animate-[bounce_3s_ease-in-out_infinite]"
                         style="background-color: var(--munoludy-pink);"></div>
                    <div class="w-16 h-16 rounded-full opacity-60 animate-[bounce_3s_ease-in-out_infinite_0.3s]"
                         style="background-color: var(--munoludy-button-bg);"></div>
                    <div class="w-16 h-16 rounded-full opacity-60 animate-[bounce_3s_ease-in-out_infinite_0.6s]"
                         style="background-color: var(--munoludy-pink);"></div>
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
