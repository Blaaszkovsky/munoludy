<x-layouts.app title="Podsumowanie">
    <x-header title="Podsumowanie" />
    <main class="flex-1 px-8 py-12 md:px-16">
        <div class="max-w-5xl mx-auto">
            <p class="text-black text-base md:text-lg text-center leading-relaxed mb-8 font-body">
                Sprawdź swoje odpowiedzi przed wysłaniem.
            </p>
            <div class="space-y-6 mb-8">
                @foreach($questions as $i => $q)
                    <div class="muno-card !p-6 md:!p-8">
                        <div class="flex items-start justify-between gap-4 mb-4">
                            <h3 class="text-xl md:text-2xl font-heading">{{ $q->title }}</h3>
                            <a href="{{ route('vote.step', ['hash' => $hash, 'n' => $i + 1]) }}" class="px-4 py-2 rounded-xl text-sm text-[var(--munoludy-button-bg)] hover:bg-[var(--munoludy-button-bg)]/10 transition">Edytuj</a>
                        </div>
                        <ul class="space-y-1 pl-1">
                            @foreach(($draft[$q->id] ?? []) as $pos => $val)
                                @if($val)
                                    <li><span class="text-[var(--munoludy-button-bg)] font-semibold">{{ $pos }}.</span> {{ $val }}</li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
            <x-form-card>
                <h2 class="text-2xl md:text-3xl mb-4 text-center font-heading text-[var(--munoludy-text)]">Gotowy do wysłania?</h2>
                <p class="text-white/70 mb-8 text-center font-body">Po kliknięciu przycisku głos zostanie przesłany i nie będzie można go zmienić.</p>
                <form method="POST" action="{{ route('vote.submit', ['hash' => $hash]) }}">
                    @csrf
                    <x-btn type="submit">Prześlij swój głos</x-btn>
                </form>
            </x-form-card>
        </div>
    </main>
    <x-footer />
</x-layouts.app>
