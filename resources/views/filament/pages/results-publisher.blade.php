<x-filament-panels::page>
    @if(!$edition)
        <x-filament::section>
            <p class="text-sm text-gray-500">Brak aktywnej edycji.</p>
        </x-filament::section>
    @else
        <x-filament::section>
            <div class="flex flex-col gap-1">
                <h2 class="text-lg font-semibold">{{ $edition->name }}</h2>
                <p class="text-sm text-gray-500">
                    Status:
                    <span class="font-medium">{{ $edition->status?->value ?? '—' }}</span>
                    @if($edition->results_published_at)
                        &middot; Opublikowano: {{ $edition->results_published_at->format('Y-m-d H:i') }}
                    @endif
                </p>
            </div>
        </x-filament::section>

        <div class="grid gap-6 md:grid-cols-2">
            <x-filament::section>
                <x-slot name="heading">Nagrody Publiczności — Top 10</x-slot>
                @forelse($publicTops as $category => $items)
                    <div class="mb-6">
                        <h3 class="font-semibold mb-2">{{ $category }}</h3>
                        @if(empty($items))
                            <p class="text-sm text-gray-400">Brak danych.</p>
                        @else
                            <ol class="space-y-1 text-sm">
                                @foreach($items as $i => $row)
                                    <li class="flex justify-between gap-4 {{ $i < 3 ? 'font-semibold' : '' }}">
                                        <span>{{ $i + 1 }}. {{ $row['label'] }}</span>
                                        <span class="text-gray-500">{{ $row['points'] }} pkt</span>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-400">Brak kategorii.</p>
                @endforelse
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Nagrody Jury — Top 10</x-slot>
                @forelse($juryTops as $category => $items)
                    <div class="mb-6">
                        <h3 class="font-semibold mb-2">{{ $category }}</h3>
                        @if(empty($items))
                            <p class="text-sm text-gray-400">Brak danych.</p>
                        @else
                            <ol class="space-y-1 text-sm">
                                @foreach($items as $i => $row)
                                    <li class="flex justify-between gap-4 {{ $i < 3 ? 'font-semibold' : '' }}">
                                        <span>{{ $i + 1 }}. {{ $row['label'] }}</span>
                                        <span class="text-gray-500">{{ $row['points'] }} pkt</span>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-400">Brak kategorii.</p>
                @endforelse
            </x-filament::section>
        </div>
    @endif
</x-filament-panels::page>
