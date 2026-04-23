@php
    /** @var \App\Models\AnswerGroup $record */
    $record = $getRecord();
    $aliases = $record->aliases;
@endphp

@if($aliases->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400">Brak aliasów.</p>
@else
    <div class="flex flex-wrap gap-2">
        @foreach($aliases as $alias)
            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-sm">
                <span class="text-gray-900 dark:text-gray-100">{{ $alias->variant }}</span>
                @if($alias->created_at)
                    <span class="text-xs text-gray-400 dark:text-gray-500">
                        {{ $alias->created_at->format('Y-m-d H:i') }}
                    </span>
                @endif
            </span>
        @endforeach
    </div>
@endif
