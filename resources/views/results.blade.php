<x-layouts.app :title="$content?->content['title'] ?? ('Wyniki — ' . $edition->name)">
    <x-header :title="$content?->content['title'] ?? ('Wyniki ' . $edition->name)" />

    @if(($previewMode ?? false))
        <div class="bg-amber-500/95 text-black px-6 py-3 text-center text-sm font-semibold tracking-wide shadow-lg">
            <span class="uppercase">Tryb testowy</span> · Widoczne wyłącznie dla zalogowanego administratora.
            Plebiscyt nie został jeszcze oficjalnie opublikowany — wyniki mogą się zmieniać.
        </div>
    @endif

    <main class="flex-1 px-8 py-12 md:px-16">
        <div class="max-w-5xl mx-auto space-y-12">

            {{-- Wstęp + statystyki --}}
            @if(!empty($content?->content['subtitle']))
                <p class="text-black text-base md:text-lg text-center leading-relaxed font-body max-w-3xl mx-auto">
                    {{ $content->content['subtitle'] }}
                </p>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @php
                    $statTiles = [
                        ['label' => 'Uczestników', 'value' => $stats['participants']],
                        ['label' => 'Głosów',      'value' => $stats['votes']],
                        ['label' => 'Kategorii',   'value' => $stats['categories']],
                    ];
                @endphp
                @foreach($statTiles as $tile)
                    <div class="muno-card !p-6 text-center">
                        <div class="text-4xl md:text-5xl font-heading text-[var(--munoludy-button-bg)] leading-none">
                            {{ $tile['value'] }}
                        </div>
                        <div class="mt-3 text-xs md:text-sm uppercase tracking-wider font-body text-black/70">
                            {{ $tile['label'] }}
                        </div>
                    </div>
                @endforeach
            </div>

            @php
                $groupSections = [
                    [
                        'title'       => 'Nagrody Publiczności',
                        'subtitle'    => 'Wybór fanów polskiej sceny klubowej',
                        'categories'  => $publicTops,
                        'accentVar'   => 'var(--munoludy-button-bg)',
                        'accentHex'   => '#ff80e3',
                        'accentSoft'  => '#fff0fa',
                        'accentRing'  => 'rgba(255, 128, 227, 0.35)',
                        'bannerText'  => 'text-black',
                        'badgeLabel'  => 'Publiczność',
                    ],
                    [
                        'title'       => 'Nagrody Jury',
                        'subtitle'    => 'Werdykt grona eksperckiego',
                        'categories'  => $juryTops,
                        'accentVar'   => 'var(--munoludy-green)',
                        'accentHex'   => '#06d473',
                        'accentSoft'  => '#e9fbf2',
                        'accentRing'  => 'rgba(6, 212, 115, 0.35)',
                        'bannerText'  => 'text-black',
                        'badgeLabel'  => 'Jury',
                    ],
                ];
            @endphp

            @foreach($groupSections as $section)
                @php
                    $categories = $section['categories'];
                    $accent     = $section['accentVar'];
                    $accentSoft = $section['accentSoft'];
                @endphp

                <section class="space-y-8">
                    {{-- Banner sekcji --}}
                    <div class="text-center space-y-3 pt-6 border-t-2 border-dashed border-black/15">
                        <span class="inline-flex items-center gap-2 px-4 py-1.5 font-heading text-xs md:text-sm uppercase tracking-widest text-black"
                              style="background-color: {{ $section['accentHex'] }};">
                            {{ $section['badgeLabel'] }}
                        </span>
                        <h2 class="text-3xl md:text-5xl font-heading text-black">
                            {{ $section['title'] }}
                        </h2>
                        <p class="text-black/70 font-body text-sm md:text-base">
                            {{ $section['subtitle'] }}
                        </p>
                    </div>

                    @if(empty($categories))
                        <x-form-card class="text-center">
                            <p class="text-black/70 font-body">Brak kategorii.</p>
                        </x-form-card>
                    @else
                        <div class="space-y-8">
                            @foreach($categories as $cat)
                                @php
                                    $items  = $cat['items'];
                                    $podium = array_slice($items, 0, 3);
                                @endphp
                                <div class="muno-card !p-6 md:!p-10">
                                    <h3 class="text-xl md:text-2xl font-heading text-center mb-10">
                                        {{ $cat['title'] }}
                                    </h3>

                                    @if(empty($items))
                                        <p class="text-black/60 text-sm text-center font-body">Brak wyników.</p>
                                    @else
                                        {{-- Podium 1-2-3 --}}
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-5 mb-8 md:items-end">
                                            @foreach($podium as $i => $row)
                                                @php
                                                    $pos = $i + 1;
                                                    $order = match($pos) {
                                                        1 => 'md:order-2',
                                                        2 => 'md:order-1',
                                                        3 => 'md:order-3',
                                                        default => '',
                                                    };
                                                    $isFirst  = $pos === 1;
                                                    $isSecond = $pos === 2;
                                                    $padTop   = $isFirst ? 'pt-14' : 'pt-12';
                                                    $padBot   = $isFirst ? 'pb-7' : 'pb-5';
                                                    $borderW  = $isFirst ? 'border-2' : 'border';
                                                    $minH     = $isFirst ? 'md:min-h-[220px]' : ($isSecond ? 'md:min-h-[180px]' : 'md:min-h-[160px]');
                                                    $labelSize= $isFirst ? 'text-lg md:text-xl' : 'text-base md:text-lg';
                                                    $cardBg   = $isFirst ? $accentSoft : '#f5f3f7';
                                                    $cardBorder = $isFirst ? $section['accentHex'] : 'rgba(0,0,0,0.1)';
                                                    $numberSize = $isFirst ? '!w-14 !h-14 !text-2xl' : '!w-11 !h-11 !text-lg';
                                                    $numberTop  = $isFirst ? '-top-8' : '-top-6';
                                                @endphp
                                                <div class="relative px-5 {{ $padTop }} {{ $padBot }} text-center {{ $borderW }} {{ $order }} {{ $minH }}"
                                                     style="background-color: {{ $cardBg }}; border-color: {{ $cardBorder }};{{ $isFirst ? ' box-shadow: 0 12px 30px -10px '.$section['accentRing'].';' : '' }}">

                                                    {{-- Numer pozycji --}}
                                                    <div class="muno-step-number absolute {{ $numberTop }} left-1/2 -translate-x-1/2 {{ $numberSize }}"
                                                         style="border-color: {{ $section['accentHex'] }}; color: {{ $section['accentHex'] }};">
                                                        {{ $pos }}
                                                    </div>

                                                    {{-- Label --}}
                                                    <div class="{{ $labelSize }} font-heading text-black break-words leading-tight">
                                                        {{ $row['label'] }}
                                                    </div>

                                                    {{-- Punkty --}}
                                                    <div class="mt-3 font-body text-black">
                                                        <span class="text-xl md:text-2xl font-heading" style="color: {{ $section['accentHex'] }};">{{ $row['points'] }}</span>
                                                        <span class="text-sm text-black/70 ml-1">pkt</span>
                                                    </div>

                                                    {{-- Liczba głosów --}}
                                                    <div class="text-xs text-black/60 font-body mt-1">
                                                        {{ $row['count'] }} głosów
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        {{-- Pełne Top 10 --}}
                                        @if(count($items) > 0)
                                            <details class="group mt-10">
                                                <summary class="cursor-pointer text-sm text-center font-body hover:opacity-80 transition list-none"
                                                         style="color: {{ $section['accentHex'] }};">
                                                    <span class="inline-flex items-center gap-2 font-semibold">
                                                        <span class="group-open:hidden">Pokaż pełne Top 10</span>
                                                        <span class="hidden group-open:inline">Ukryj Top 10</span>
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4 transition group-open:rotate-180">
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
                                                        <li class="flex items-center gap-3 md:gap-4 px-4 py-3 border border-black/10"
                                                            style="background-color: {{ $isPodiumRow ? '#f5f3f7' : '#ffffff' }};">
                                                            <span class="shrink-0 w-8 text-center font-heading font-bold text-base"
                                                                  style="color: {{ $section['accentHex'] }};">{{ $pos }}.</span>
                                                            <div class="flex-1 min-w-0">
                                                                <div class="truncate text-sm md:text-base text-black {{ $isPodiumRow ? 'font-semibold' : '' }}">
                                                                    {{ $row['label'] }}
                                                                </div>
                                                                <div class="mt-2 h-2 bg-black/10 overflow-hidden">
                                                                    <div class="h-full" style="width: {{ $row['pct'] }}%; background-color: {{ $section['accentHex'] }};"></div>
                                                                </div>
                                                            </div>
                                                            <div class="shrink-0 text-right">
                                                                <div class="text-sm font-semibold text-black">{{ $row['points'] }} pkt</div>
                                                                <div class="text-xs text-black/60 font-body">{{ $row['count'] }} głosów</div>
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
