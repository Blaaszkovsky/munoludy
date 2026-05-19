@props(['questionId', 'points' => [5,4,3,2,1], 'values' => []])
<div class="space-y-3">
    @foreach($points as $i => $p)
        <div class="flex items-center gap-4">
            <span class="flex-shrink-0 w-14 h-14 flex items-center justify-center text-black font-heading text-base font-extrabold"
                  style="background-color: var(--munoludy-button-bg);">
                {{ $p }} pkt
            </span>
            <input type="text" name="answers[{{ $questionId }}][{{ $i + 1 }}]"
                value="{{ $values[$i + 1] ?? '' }}"
                placeholder="{{ $i + 1 }}. miejsce"
                class="flex-1 px-5 py-4 bg-white border border-black/20 text-black placeholder:text-black/40 focus:outline-none focus:ring-2 focus:ring-[var(--munoludy-button-bg)] transition-all font-body"
                maxlength="255">
        </div>
    @endforeach
</div>
