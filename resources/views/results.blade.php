<x-layouts.app :title="$content?->content['title'] ?? ('Wyniki — ' . $edition->name)">
    <x-header :title="$content?->content['title'] ?? ('Wyniki ' . $edition->name)" />

    <main class="flex-1 px-6 py-12 md:px-16">
        <div class="max-w-6xl mx-auto space-y-20">

            {{-- Hero engagement --}}
            <section class="text-center">
                @if(!empty($content?->content['subtitle']))
                    <p class="text-white/90 text-center text-lg md:text-xl leading-relaxed font-body max-w-3xl mx-auto mb-10">
                        {{ $content->content['subtitle'] }}
                    </p>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 max-w-4xl mx-auto">
                    <div class="rounded-2xl bg-white/5 backdrop-blur-lg border border-white/10 p-6">
                        <div class="text-4xl md:text-5xl font-heading text-[var(--munoludy-accent,#ffd24d)]">
                            {{ $stats['participants'] }}
                        </div>
                        <div class="mt-2 text-sm uppercase tracking-wider text-white/70">Uczestników</div>
                    </div>
                    <div class="rounded-2xl bg-white/5 backdrop-blur-lg border border-white/10 p-6">
                        <div class="text-4xl md:text-5xl font-heading text-[var(--munoludy-accent,#ffd24d)]">
                            {{ $stats['votes'] }}
                        </div>
                        <div class="mt-2 text-sm uppercase tracking-wider text-white/70">Głosów</div>
                    </div>
                    <div class="rounded-2xl bg-white/5 backdrop-blur-lg border border-white/10 p-6">
                        <div class="text-4xl md:text-5xl font-heading text-[var(--munoludy-accent,#ffd24d)]">
                            {{ $stats['categories'] }}
                        </div>
                        <div class="mt-2 text-sm uppercase tracking-wider text-white/70">Kategorii</div>
                    </div>
                </div>
            </section>

            @php
                $groupSections = [
                    ['Nagrody Publiczności', $publicTops],
                    ['Nagrody Jury', $juryTops],
                ];
            @endphp

            @foreach($groupSections as [$sectionTitle, $categories])
                <section>
                    <h2 class="text-3xl md:text-4xl font-heading text-white text-center mb-10">
                        {{ $sectionTitle }}
                    </h2>

                    @if(empty($categories))
                        <p class="text-white/60 text-center">Brak kategorii.</p>
                    @else
                        <div class="space-y-10">
                            @foreach($categories as $cat)
                                @php
                                    $items = $cat['items'];
                                    $podium = array_slice($items, 0, 3);
                                    $rest = array_slice($items, 3);
                                @endphp
                                <div class="rounded-3xl bg-white/5 backdrop-blur-xl border border-white/10 p-6 md:p-8">
                                    <h3 class="text-xl md:text-2xl font-heading text-white text-center mb-6">
                                        {{ $cat['title'] }}
                                    </h3>

                                    @if(empty($items))
                                        <p class="text-white/60 text-sm text-center">Brak wyników.</p>
                                    @else
                                        {{-- Podium 1-2-3 --}}
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                            @foreach($podium as $i => $row)
                                                @php
                                                    $pos = $i + 1;
                                                    $podiumStyle = match($pos) {
                                                        1 => 'bg-yellow-500/20 border-yellow-400/50',
                                                        2 => 'bg-gray-300/10 border-gray-300/40',
                                                        3 => 'bg-amber-700/20 border-amber-600/40',
                                                        default => 'bg-white/5 border-white/10',
                                                    };
                                                    $order = match($pos) {
                                                        1 => 'md:order-2',
                                                        2 => 'md:order-1',
                                                        3 => 'md:order-3',
                                                        default => '',
                                                    };
                                                    $scale = $pos === 1 ? 'md:scale-105' : '';
                                                @endphp
                                                <div class="relative rounded-2xl border p-5 text-center {{ $podiumStyle }} {{ $order }} {{ $scale }}">
                                                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 inline-flex items-center justify-center w-8 h-8 rounded-full bg-[var(--munoludy-accent,#ffd24d)] text-black font-bold text-sm shadow">
                                                        {{ $pos }}
                                                    </div>
                                                    <div class="mt-2 text-base md:text-lg font-semibold text-white break-words">
                                                        {{ $row['label'] }}
                                                    </div>
                                                    <div class="mt-2 text-sm text-white/80">
                                                        {{ $row['points'] }} pkt
                                                    </div>
                                                    <div class="text-xs text-white/50 mt-0.5">
                                                        {{ $row['count'] }} wskazań
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        {{-- Expandable full top 10 --}}
                                        @if(count($items) > 0)
                                            <details class="group">
                                                <summary class="cursor-pointer text-sm text-center text-white/70 hover:text-white transition list-none">
                                                    <span class="inline-flex items-center gap-2">
                                                        <span class="group-open:hidden">Pokaż pełne Top 10</span>
                                                        <span class="hidden group-open:inline">Ukryj Top 10</span>
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 transition group-open:rotate-180">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                        </svg>
                                                    </span>
                                                </summary>

                                                <ol class="mt-6 space-y-2">
                                                    @foreach($items as $i => $row)
                                                        @php
                                                            $pos = $i + 1;
                                                            $isPodiumRow = $pos <= 3;
                                                        @endphp
                                                        <li class="flex items-center gap-3 px-4 py-2.5 rounded-xl border
                                                            {{ $isPodiumRow ? 'bg-white/10 border-white/20' : 'bg-white/5 border-white/10' }}">
                                                            <span class="shrink-0 w-8 text-center font-mono text-white/80">{{ $pos }}.</span>
                                                            <div class="flex-1 min-w-0">
                                                                <div class="truncate text-sm md:text-base text-white {{ $isPodiumRow ? 'font-semibold' : '' }}">
                                                                    {{ $row['label'] }}
                                                                </div>
                                                                <div class="mt-1 h-1.5 bg-white/10 rounded-full overflow-hidden">
                                                                    <div class="h-full rounded-full bg-[var(--munoludy-accent,#ffd24d)]" style="width: {{ $row['pct'] }}%"></div>
                                                                </div>
                                                            </div>
                                                            <div class="shrink-0 text-right">
                                                                <div class="text-sm font-semibold text-white">{{ $row['points'] }} pkt</div>
                                                                <div class="text-xs text-white/50">{{ $row['count'] }}</div>
                                                            </div>
                                                        </li>
                                                    @endforeach
                                                </ol>
                                            </details>
                                        @endif
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endforeach
        </div>
    </main>

    <x-footer />
</x-layouts.app>
