<footer class="px-8 py-8 md:px-16">
    <div class="max-w-6xl mx-auto flex flex-col md:flex-row items-center justify-center gap-4">
        <div class="flex items-center gap-4">
            <img src="{{ asset('images/muno-logo-dark.svg') }}" alt="Muno.pl" class="h-6">
            <span class="text-black/70 text-sm font-body">&amp;</span>
            <img src="{{ asset('images/biletomat-logo.svg') }}" alt="Biletomat" class="h-7">
        </div>
        <span class="text-black/70 text-sm font-body">&copy; {{ now()->year }} muno.pl &amp; biletomat.pl</span>
    </div>
</footer>
