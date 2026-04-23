@props(['title' => 'Munoludy 2025', 'poweredBy' => 'powered by'])
<header class="px-8 py-8 md:px-16 md:py-12">
    <div class="max-w-6xl mx-auto flex flex-col items-center justify-center gap-3">
        <h1 class="text-3xl md:text-5xl lg:text-6xl text-white text-center font-heading">{{ $title }}</h1>
        <div class="flex items-center gap-3">
            <span class="text-white text-lg md:text-xl font-heading">{{ $poweredBy }}</span>
            <img src="{{ asset('images/biletomat-logo.svg') }}" alt="Biletomat" class="h-7 md:h-9">
        </div>
    </div>
</header>
