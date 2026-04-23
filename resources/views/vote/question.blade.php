<x-layouts.app title="Krok {{ $step }} z {{ $total }}">
    <header class="px-8 py-6 md:px-16 md:py-8">
        <div class="max-w-5xl mx-auto flex items-center justify-between">
            <span class="text-white font-heading text-xl">Munoludy</span>
            <div class="text-white/60 text-sm md:text-base font-body">Krok {{ $step }} z {{ $total }}</div>
        </div>
    </header>
    <div class="px-8 md:px-16 mb-8">
        <div class="max-w-5xl mx-auto">
            <div class="h-2 bg-white/10 rounded-full overflow-hidden">
                <div class="h-full transition-all duration-500 rounded-full bg-[var(--munoludy-button-bg)]" style="width: {{ ($step/$total)*100 }}%"></div>
            </div>
        </div>
    </div>
    <main class="flex-1 px-8 py-6 md:px-16 md:py-12">
        <div class="max-w-4xl mx-auto" x-data="voteForm({{ $question->id }}, @js($draft))">
            <x-form-card>
                <div class="inline-flex items-center justify-center w-12 h-12 md:w-14 md:h-14 rounded-2xl mb-6 bg-[var(--munoludy-pink)]/80">
                    <span class="text-white text-xl md:text-2xl font-heading">{{ $step }}</span>
                </div>
                <h2 class="text-3xl md:text-5xl mb-4 font-heading text-[var(--munoludy-text)]">{{ $question->title }}</h2>
                <p class="text-white/70 mb-8 text-base md:text-lg font-body">{{ $question->description }}</p>

                <form method="POST" action="{{ route('vote.save-step', ['hash' => $hash, 'n' => $step]) }}" class="space-y-6">
                    @csrf
                    @if($question->field_type->value === 'ranked_text_5')
                        <x-ranked-input :questionId="$question->id" :points="$question->ranked_points ?? [5,4,3,2,1]" :values="$draft" />
                    @elseif(in_array($question->field_type->value, ['text','textarea']))
                        @if($question->field_type->value === 'textarea')
                            <textarea name="answers[{{ $question->id }}][1]" rows="6" class="w-full px-5 py-4 rounded-2xl bg-white/10 border border-white/20 text-white placeholder-white/40">{{ $draft[1] ?? '' }}</textarea>
                        @else
                            <input type="text" name="answers[{{ $question->id }}][1]" value="{{ $draft[1] ?? '' }}" class="w-full px-5 py-4 rounded-2xl bg-white/10 border border-white/20 text-white" maxlength="255">
                        @endif
                    @elseif(in_array($question->field_type->value, ['radio','select']))
                        @foreach($question->options as $opt)
                            <label class="flex items-center gap-3 text-white">
                                <input type="radio" name="answers[{{ $question->id }}][1]" value="{{ $opt->id }}" {{ ($draft[1] ?? null) == $opt->id ? 'checked' : '' }}>
                                <span>{{ $opt->label }}</span>
                            </label>
                        @endforeach
                    @elseif($question->field_type->value === 'checkbox')
                        @foreach($question->options as $i => $opt)
                            <label class="flex items-center gap-3 text-white">
                                <input type="checkbox" name="answers[{{ $question->id }}][{{ $i }}]" value="{{ $opt->id }}" {{ in_array($opt->id, (array)($draft ?? [])) ? 'checked' : '' }}>
                                <span>{{ $opt->label }}</span>
                            </label>
                        @endforeach
                    @endif

                    <div class="flex flex-col sm:flex-row gap-4">
                        <button type="submit" name="direction" value="prev" class="flex-1 py-4 rounded-2xl font-semibold text-lg border-2 border-[var(--munoludy-button-bg)] text-[var(--munoludy-button-bg)] hover:bg-white/5 font-heading">Poprzednie</button>
                        <button type="submit" name="direction" value="next" class="flex-1 py-4 rounded-2xl font-semibold text-lg bg-[var(--munoludy-button-bg)] text-[var(--munoludy-button-text)] hover:scale-[1.02] font-heading">{{ $step < $total ? 'Następne' : 'Podsumowanie' }}</button>
                    </div>
                </form>
            </x-form-card>
        </div>
    </main>
    <x-footer />
    @push('scripts')
    <script>
    function voteForm(qid, initial) {
        return {
            key: 'munoludy_draft_'+window.location.pathname.split('/')[2],
            init() {
                const stored = JSON.parse(localStorage.getItem(this.key) || '{}');
                const merged = { ...(stored[qid] || {}), ...(initial || {}) };
                Object.entries(merged).forEach(([k,v]) => {
                    const el = document.querySelector(`[name="answers[${qid}][${k}]"]`);
                    if (el && el.type !== 'checkbox' && el.type !== 'radio') el.value = v;
                });
                document.querySelectorAll(`[name^="answers[${qid}]"]`).forEach(el => {
                    el.addEventListener('input', () => this.persist(qid));
                });
            },
            persist(qid) {
                const draft = JSON.parse(localStorage.getItem(this.key) || '{}');
                draft[qid] = draft[qid] || {};
                document.querySelectorAll(`[name^="answers[${qid}]"]`).forEach(el => {
                    const match = el.name.match(/answers\[\d+\]\[(\d+)\]/);
                    if (match) draft[qid][match[1]] = el.value;
                });
                localStorage.setItem(this.key, JSON.stringify(draft));
            },
        };
    }
    </script>
    @endpush
</x-layouts.app>
