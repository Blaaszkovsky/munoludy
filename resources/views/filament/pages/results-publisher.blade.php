<x-filament-panels::page>
    @if(!$edition)
        <x-filament::section>
            <p class="text-sm text-gray-500">Brak aktywnej edycji.</p>
        </x-filament::section>
    @else
        {{-- Hero / engagement --}}
        <x-filament::section>
            <div class="flex flex-col gap-4">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $edition->name }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Status: <span class="font-medium">{{ $edition->status?->value ?? '—' }}</span>
                            @if($edition->results_published_at)
                                · Opublikowano: {{ $edition->results_published_at->format('Y-m-d H:i') }}
                            @endif
                        </p>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 md:text-right">
                        @if($edition->starts_at || $edition->ends_at)
                            <div>
                                Głosowanie:
                                <span class="font-medium text-gray-700 dark:text-gray-300">
                                    {{ $edition->starts_at?->format('Y-m-d') ?? '—' }}
                                    →
                                    {{ $edition->ends_at?->format('Y-m-d') ?? '—' }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="rounded-xl bg-primary-50 dark:bg-primary-500/10 border border-primary-200 dark:border-primary-500/30 p-4">
                        <div class="text-xs uppercase tracking-wide text-primary-700 dark:text-primary-300">Zarejestrowani</div>
                        <div class="mt-1 text-2xl font-bold text-primary-900 dark:text-primary-100">{{ $engagement['registered_total'] }}</div>
                        <div class="text-xs text-primary-700/70 dark:text-primary-300/70 mt-1">
                            Publ.: {{ $engagement['registered_public'] }} · Jury: {{ $engagement['registered_jury'] }}
                        </div>
                    </div>
                    <div class="rounded-xl bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/30 p-4">
                        <div class="text-xs uppercase tracking-wide text-green-700 dark:text-green-300">Zagłosowali</div>
                        <div class="mt-1 text-2xl font-bold text-green-900 dark:text-green-100">{{ $engagement['voted_total'] }}</div>
                        <div class="text-xs text-green-700/70 dark:text-green-300/70 mt-1">
                            {{ number_format($engagement['voted_pct'], 1, ',', ' ') }}% zarejestrowanych
                        </div>
                    </div>
                    <div class="rounded-xl bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/30 p-4">
                        <div class="text-xs uppercase tracking-wide text-indigo-700 dark:text-indigo-300">Odpowiedzi łącznie</div>
                        <div class="mt-1 text-2xl font-bold text-indigo-900 dark:text-indigo-100">{{ $engagement['answers_total'] }}</div>
                        <div class="text-xs text-indigo-700/70 dark:text-indigo-300/70 mt-1">
                            we wszystkich kategoriach
                        </div>
                    </div>
                    <div class="rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 p-4">
                        <div class="text-xs uppercase tracking-wide text-amber-700 dark:text-amber-300">Kategorii</div>
                        <div class="mt-1 text-2xl font-bold text-amber-900 dark:text-amber-100">{{ $engagement['categories'] }}</div>
                        <div class="text-xs text-amber-700/70 dark:text-amber-300/70 mt-1">
                            pytań w edycji
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::section>

        <div class="space-y-6">
            @foreach([
                ['Nagrody Publiczności', $publicTops, 'primary'],
                ['Nagrody Jury', $juryTops, 'warning'],
            ] as [$heading, $categories, $color])
                <x-filament::section>
                    <x-slot name="heading">{{ $heading }}</x-slot>

                    @forelse($categories as $cat)
                        <div class="mb-8 last:mb-0">
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $cat['title'] }}</h3>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ $cat['audience'] === 'jury' ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300' : 'bg-primary-100 text-primary-700 dark:bg-primary-500/20 dark:text-primary-300' }}">
                                        {{ $cat['audience'] === 'jury' ? 'Jury' : ($cat['audience'] === 'both' ? 'Oba' : 'Publiczność') }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $cat['unique_count'] }} grup · {{ $cat['total_count'] }} wskazań
                                    </div>
                                    <button type="button"
                                        wire:click="autoAssignPodium({{ $cat['question_id'] }})"
                                        wire:confirm="Ustawić podium automatycznie na podstawie punktów (top 3)? Aktualne ręczne ustawienia w tej kategorii zostaną nadpisane."
                                        class="text-xs px-2 py-1 rounded border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800"
                                        title="Top 3 wg punktów → podium 1/2/3.">
                                        Auto-podium
                                    </button>
                                    <button type="button"
                                        wire:click="clearPodium({{ $cat['question_id'] }})"
                                        wire:confirm="Usunąć wszystkie ręczne ustawienia podium w tej kategorii?"
                                        class="text-xs px-2 py-1 rounded border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800"
                                        title="Usuń wszystkie ustawienia podium w tej kategorii.">
                                        Wyczyść podium
                                    </button>
                                </div>
                            </div>

                            @if(empty($cat['top']))
                                <p class="text-sm text-gray-400">Brak danych.</p>
                            @else
                                <ol class="space-y-1.5">
                                    @foreach($cat['top'] as $i => $row)
                                        @php
                                            $position = $i + 1;
                                            $isPodiumPos = $position <= 3;
                                            $hasOverride = $row['points_override'] !== null;
                                        @endphp
                                        <li class="flex flex-wrap md:flex-nowrap items-center gap-3 px-3 py-2 rounded-lg
                                            {{ $isPodiumPos ? 'bg-gray-50 dark:bg-gray-800/50' : '' }}">
                                            <span class="shrink-0 w-7 text-center font-mono text-sm
                                                {{ $isPodiumPos ? 'font-bold text-gray-900 dark:text-gray-100' : 'text-gray-500' }}">
                                                {{ $position }}.
                                            </span>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <span class="truncate text-sm {{ $isPodiumPos ? 'font-semibold text-gray-900 dark:text-gray-100' : 'text-gray-800 dark:text-gray-200' }}">
                                                        {{ $row['label'] }}
                                                    </span>
                                                    @if($row['is_podium'])
                                                        <span class="shrink-0 inline-flex items-center gap-1 text-xs text-amber-600 dark:text-amber-400" title="Ręcznie ustawione miejsce: {{ $row['podium_position'] }}">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0" />
                                                            </svg>
                                                            {{ $row['podium_position'] ?? '' }}
                                                        </span>
                                                    @endif
                                                    @if($hasOverride)
                                                        <span class="shrink-0 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] uppercase tracking-wide bg-pink-100 text-pink-700 dark:bg-pink-500/20 dark:text-pink-300" title="Punkty ustawione ręcznie. Auto: {{ $row['aggregated_points'] }}">
                                                            ręczne pkt
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="mt-1 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                                    <div class="h-full rounded-full {{ $color === 'warning' ? 'bg-amber-500' : 'bg-primary-500' }}" style="width: {{ $row['pct'] }}%"></div>
                                                </div>
                                            </div>
                                            <div class="shrink-0 text-right">
                                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $row['points'] }} pkt</div>
                                                <div class="text-xs text-gray-400">{{ $row['count'] }} wskazań</div>
                                            </div>
                                            <div
                                                x-data="{
                                                    editing: false,
                                                    value: @js($row['points']),
                                                    save() {
                                                        const n = this.value === '' || this.value === null ? null : parseInt(this.value, 10);
                                                        if (n !== null && (isNaN(n) || n < 0)) { return; }
                                                        $wire.setPointsOverride({{ $row['id'] }}, n);
                                                        this.editing = false;
                                                    },
                                                    reset() {
                                                        $wire.setPointsOverride({{ $row['id'] }}, null);
                                                        this.editing = false;
                                                    }
                                                }"
                                                class="shrink-0 flex items-center gap-1">
                                                <template x-if="!editing">
                                                    <button type="button" @click="editing = true; $nextTick(() => $refs.pointsInput.focus())"
                                                        class="text-xs px-2 py-1 rounded border border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800"
                                                        title="Ustaw ręcznie liczbę punktów (override).">
                                                        Edytuj pkt
                                                    </button>
                                                </template>
                                                <template x-if="editing">
                                                    <div class="flex items-center gap-1">
                                                        <input x-ref="pointsInput" type="number" min="0" x-model.number="value"
                                                            @keydown.enter.prevent="save()" @keydown.escape="editing = false"
                                                            class="w-20 text-xs px-2 py-1 rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900" />
                                                        <button type="button" @click="save()" class="text-xs px-2 py-1 rounded bg-primary-600 text-white hover:bg-primary-700">OK</button>
                                                        @if($hasOverride)
                                                            <button type="button" @click="reset()" class="text-xs px-2 py-1 rounded border border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800" title="Przywróć punkty automatyczne">
                                                                ↺
                                                            </button>
                                                        @endif
                                                    </div>
                                                </template>
                                            </div>
                                            <div class="shrink-0 flex items-center gap-1" role="group" aria-label="Ustaw miejsce podium">
                                                @foreach([1, 2, 3] as $slot)
                                                    @php
                                                        $isActive = $row['is_podium'] && (int) $row['podium_position'] === $slot;
                                                    @endphp
                                                    <button type="button"
                                                        wire:click="setPodiumPosition({{ $row['id'] }}, {{ $slot }})"
                                                        wire:confirm="Ustawić {{ $slot }}. miejsce dla: {{ addslashes($row['label']) }}? Jeśli ktoś inny zajmuje to miejsce, zostanie zamieniony."
                                                        class="text-xs w-7 h-7 rounded-full font-semibold border
                                                            {{ $isActive
                                                                ? 'bg-amber-500 border-amber-600 text-white'
                                                                : 'bg-transparent border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}"
                                                        title="Ręcznie ustaw {{ $slot }}. miejsce">
                                                        {{ $slot }}
                                                    </button>
                                                @endforeach
                                                <button type="button"
                                                    wire:click="togglePodium({{ $row['id'] }})"
                                                    @class([
                                                        'text-xs w-7 h-7 rounded-full font-semibold border',
                                                        'bg-transparent border-gray-300 dark:border-gray-700 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800' => $row['is_podium'],
                                                        'opacity-30 cursor-default border-transparent' => ! $row['is_podium'],
                                                    ])
                                                    title="Usuń z podium">
                                                    ×
                                                </button>
                                            </div>
                                        </li>
                                    @endforeach
                                </ol>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">Brak kategorii.</p>
                    @endforelse
                </x-filament::section>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
