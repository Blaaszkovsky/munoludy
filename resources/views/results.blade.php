<x-layouts.app :title="$content?->content['title'] ?? ('Wyniki — ' . $edition->name)">
    <x-header :title="$content?->content['title'] ?? ('Wyniki ' . $edition->name)" />

    <main class="flex-1 px-8 py-12 md:px-16">
        <div class="max-w-5xl mx-auto space-y-16">
            @if(!empty($content?->content['subtitle']))
                <p class="text-white/90 text-center text-lg md:text-xl leading-relaxed font-body max-w-3xl mx-auto">
                    {{ $content->content['subtitle'] }}
                </p>
            @endif

            {{-- Publiczność --}}
            <section>
                <h2 class="text-3xl md:text-4xl font-heading text-white text-center mb-10">
                    Nagrody Publiczności
                </h2>
                <div class="grid gap-8 md:grid-cols-2">
                    @forelse($publicTops as $category => $items)
                        <x-form-card>
                            <h3 class="text-xl md:text-2xl font-heading text-[var(--munoludy-text)] mb-6 text-center">
                                {{ $category }}
                            </h3>
                            @if(empty($items))
                                <p class="text-white/60 text-sm text-center">Brak wyników.</p>
                            @else
                                <ol class="space-y-2">
                                    @foreach($items as $i => $row)
                                        @php
                                            $position = $i + 1;
                                            $podiumClass = match(true) {
                                                $position === 1 => 'bg-yellow-500/30 border-yellow-400/60 text-white font-bold',
                                                $position === 2 => 'bg-gray-300/20 border-gray-300/50 text-white font-semibold',
                                                $position === 3 => 'bg-amber-700/30 border-amber-600/50 text-white font-semibold',
                                                default => 'bg-white/5 border-white/10 text-white/90',
                                            };
                                        @endphp
                                        <li class="flex items-center gap-3 px-4 py-2 rounded-xl border {{ $podiumClass }}">
                                            <span class="shrink-0 w-7 text-center font-mono">{{ $position }}.</span>
                                            <span class="flex-1 truncate">{{ $row['label'] }}</span>
                                            <span class="shrink-0 text-sm opacity-80">{{ $row['points'] }} pkt</span>
                                        </li>
                                    @endforeach
                                </ol>
                            @endif
                        </x-form-card>
                    @empty
                        <p class="text-white/60 text-center md:col-span-2">Brak kategorii.</p>
                    @endforelse
                </div>
            </section>

            {{-- Jury --}}
            <section>
                <h2 class="text-3xl md:text-4xl font-heading text-white text-center mb-10">
                    Nagrody Jury
                </h2>
                <div class="grid gap-8 md:grid-cols-2">
                    @forelse($juryTops as $category => $items)
                        <x-form-card>
                            <h3 class="text-xl md:text-2xl font-heading text-[var(--munoludy-text)] mb-6 text-center">
                                {{ $category }}
                            </h3>
                            @if(empty($items))
                                <p class="text-white/60 text-sm text-center">Brak wyników.</p>
                            @else
                                <ol class="space-y-2">
                                    @foreach($items as $i => $row)
                                        @php
                                            $position = $i + 1;
                                            $podiumClass = match(true) {
                                                $position === 1 => 'bg-yellow-500/30 border-yellow-400/60 text-white font-bold',
                                                $position === 2 => 'bg-gray-300/20 border-gray-300/50 text-white font-semibold',
                                                $position === 3 => 'bg-amber-700/30 border-amber-600/50 text-white font-semibold',
                                                default => 'bg-white/5 border-white/10 text-white/90',
                                            };
                                        @endphp
                                        <li class="flex items-center gap-3 px-4 py-2 rounded-xl border {{ $podiumClass }}">
                                            <span class="shrink-0 w-7 text-center font-mono">{{ $position }}.</span>
                                            <span class="flex-1 truncate">{{ $row['label'] }}</span>
                                            <span class="shrink-0 text-sm opacity-80">{{ $row['points'] }} pkt</span>
                                        </li>
                                    @endforeach
                                </ol>
                            @endif
                        </x-form-card>
                    @empty
                        <p class="text-white/60 text-center md:col-span-2">Brak kategorii.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </main>

    <x-footer />
</x-layouts.app>
