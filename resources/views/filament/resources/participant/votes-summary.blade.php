@php
    /** @var \App\Models\Participant $record */
    $record = $getRecord();
    $submission = $record->submission;
    $answers = $submission?->answers ?? collect();

    // Group answers by question_id preserving question model
    $grouped = $answers->groupBy('question_id');
@endphp

@if(!$submission)
    <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 p-6 text-center text-sm text-gray-500 dark:text-gray-400">
        <p class="font-medium">Uczestnik jeszcze nie zagłosował.</p>
        <p class="mt-1 text-xs">Gdy oddany zostanie głos, pojawi się tu pełna lista odpowiedzi.</p>
    </div>
@elseif($answers->isEmpty())
    <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 p-6 text-center text-sm text-gray-500 dark:text-gray-400">
        <p class="font-medium">Zgłoszenie istnieje, ale nie zawiera odpowiedzi.</p>
    </div>
@else
    <div class="space-y-6">
        @foreach($grouped as $questionId => $questionAnswers)
            @php
                $question = $questionAnswers->first()->question;
                $isRanked = $question && $question->field_type?->value === 'ranked_text_5';
                $sorted = $isRanked
                    ? $questionAnswers->sortBy(fn ($a) => $a->position ?? 99)
                    : $questionAnswers;
            @endphp
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                            {{ $question?->title ?? 'Pytanie #' . $questionId }}
                        </h3>
                        @if($question?->audience)
                            @php
                                $aud = $question->audience->value;
                                $audLabel = match($aud) {
                                    'public' => 'Publiczność',
                                    'jury' => 'Jury',
                                    'both' => 'Oba',
                                    default => $aud,
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-700 dark:bg-primary-500/20 dark:text-primary-300">
                                {{ $audLabel }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700/70">
                    @foreach($sorted as $answer)
                        <div class="flex items-start gap-3 px-4 py-3 text-sm">
                            @if($isRanked)
                                <span class="shrink-0 inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary-600 text-white text-xs font-bold">
                                    {{ $answer->position ?? '—' }}
                                </span>
                            @else
                                <span class="shrink-0 inline-flex items-center justify-center w-7 h-7 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs font-bold">
                                    •
                                </span>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-gray-900 dark:text-gray-100 break-words">
                                    {{ $answer->value ?? $answer->questionOption?->label ?? '—' }}
                                </div>
                                @if($answer->group)
                                    <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                        Grupa: <span class="font-medium">{{ $answer->group->canonical_label }}</span>
                                    </div>
                                @elseif($answer->value_normalized && $answer->value_normalized !== $answer->value)
                                    <div class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                                        Znormalizowane: {{ $answer->value_normalized }}
                                    </div>
                                @endif
                            </div>
                            <div class="shrink-0 text-right">
                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {{ (int) ($answer->points ?? 0) }} pkt
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endif
