@php
    /** @var \App\Models\AnswerGroup $record */
    $record = $getRecord();
    $answers = $record->answers;
    $isRanked = $record->question?->field_type?->value === 'ranked_text_5';

    // Position distribution for ranked_text_5
    $positionCounts = [];
    if ($isRanked) {
        for ($i = 1; $i <= 5; $i++) {
            $positionCounts[$i] = $answers->where('position', $i)->count();
        }
    }
    $maxPosCount = empty($positionCounts) ? 0 : max($positionCounts);
@endphp

@if($isRanked && array_sum($positionCounts) > 0)
    <div class="mb-6 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Rozkład pozycji</h4>
        <div class="space-y-2">
            @foreach($positionCounts as $pos => $count)
                @php $pct = $maxPosCount > 0 ? ($count / $maxPosCount) * 100 : 0; @endphp
                <div class="flex items-center gap-3">
                    <span class="shrink-0 w-20 text-xs text-gray-500 dark:text-gray-400">Miejsce {{ $pos }}</span>
                    <div class="flex-1 bg-gray-100 dark:bg-gray-800 rounded-full h-3 overflow-hidden">
                        <div class="bg-primary-500 h-full rounded-full" style="width: {{ $pct }}%"></div>
                    </div>
                    <span class="shrink-0 w-12 text-right text-xs font-medium text-gray-700 dark:text-gray-300">{{ $count }}</span>
                </div>
            @endforeach
        </div>
    </div>
@endif

@if($answers->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400">Brak odpowiedzi powiązanych z grupą.</p>
@else
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="border-b border-gray-200 dark:border-gray-700">
                <tr class="text-left text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    <th class="px-3 py-2">Uczestnik</th>
                    @if($isRanked)
                        <th class="px-3 py-2">Poz.</th>
                    @endif
                    <th class="px-3 py-2">Wartość</th>
                    <th class="px-3 py-2 text-right">Pkt</th>
                    <th class="px-3 py-2">Data</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach($answers->take(200) as $answer)
                    <tr>
                        <td class="px-3 py-2 text-gray-900 dark:text-gray-100">
                            {{ $answer->submission?->participant?->email ?? '—' }}
                        </td>
                        @if($isRanked)
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $answer->position ?? '—' }}</td>
                        @endif
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $answer->value ?? '—' }}</td>
                        <td class="px-3 py-2 text-right font-medium text-gray-900 dark:text-gray-100">{{ (int) ($answer->points ?? 0) }}</td>
                        <td class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400">
                            {{ $answer->submission?->submitted_at?->format('Y-m-d H:i') ?? '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if($answers->count() > 200)
            <p class="mt-3 text-xs text-gray-400">Wyświetlono pierwsze 200 z {{ $answers->count() }} odpowiedzi.</p>
        @endif
    </div>
@endif
