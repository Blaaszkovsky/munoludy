@props(['questionId', 'points' => [5,4,3,2,1], 'values' => []])
<div class="space-y-3">
    @foreach($points as $i => $p)
        <div class="flex items-center gap-4">
            <span class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center bg-[var(--munoludy-pink)]/80 text-white font-heading text-lg">
                {{ $p }} pkt
            </span>
            <input type="text" name="answers[{{ $questionId }}][{{ $i + 1 }}]"
                value="{{ $values[$i + 1] ?? '' }}"
                placeholder="{{ $i + 1 }}. miejsce"
                class="flex-1 px-5 py-4 rounded-2xl bg-white/10 border border-white/20 text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-[var(--munoludy-button-bg)] transition-all font-body"
                maxlength="255">
        </div>
    @endforeach
</div>
