@props(['title' => 'Munoludy 2025', 'poweredBy' => 'powered by'])
<header class="px-8 py-8 md:px-16 md:py-12">
    <div class="max-w-6xl mx-auto flex flex-col items-center justify-center gap-5">
        <h1 class="sr-only">{{ $title }}</h1>
        <a href="{{ config('munoludy.logo_url') }}" class="block w-full max-w-[480px]" aria-label="{{ $title }}">
            <img src="{{ asset('images/MUNOLUDY.png') }}"
                 alt="Munoludy 2025 – Nagrody Polskiej Sceny Klubowej"
                 class="w-full h-auto">
        </a>
        <div class="flex items-center gap-3">
            <span class="text-black text-lg md:text-xl font-heading">{{ $poweredBy }}</span>
            <img src="{{ asset('images/biletomat-logo.svg') }}" alt="biletomat" class="h-7 md:h-9">
        </div>
    </div>
</header>
