<x-layouts.app title="Krok {{ $step }} z {{ $total }}">
    <x-header title="Munoludy 2025" />
    <div class="px-8 md:px-16 mb-8">
        <div class="max-w-5xl mx-auto flex flex-col gap-3">
            <div class="flex justify-end">
                <span class="muno-step-pill">Krok {{ $step }} z {{ $total }}</span>
            </div>
            <div class="muno-step-bar">
                <span style="width: {{ ($step/$total)*100 }}%"></span>
            </div>
        </div>
    </div>
    <main class="flex-1 px-8 py-6 md:px-16 md:py-12">
        <div class="max-w-4xl mx-auto" x-data="voteForm({{ $question->id }}, @js($draft))">
            <x-form-card>
                <div class="muno-step-number mb-6">{{ $step }}</div>
                <h2 class="text-3xl md:text-5xl mb-4 font-heading">{{ $question->title }}</h2>
                <p class="mb-8 text-base md:text-lg font-body text-black/70">{{ $question->description }}</p>

                <form method="POST" action="{{ route('vote.save-step', ['hash' => $hash, 'n' => $step]) }}" class="space-y-6">
                    @csrf
                    @if($question->field_type->value === 'ranked_text_5')
                        <x-ranked-input :questionId="$question->id" :points="$question->ranked_points ?? [5,4,3,2,1]" :values="$draft" />
                    @elseif(in_array($question->field_type->value, ['text','textarea']))
                        @if($question->field_type->value === 'textarea')
                            <textarea name="answers[{{ $question->id }}][1]" rows="6" class="w-full px-5 py-4 bg-white border border-black/20 text-black placeholder:text-black/40">{{ $draft[1] ?? '' }}</textarea>
                        @else
                            <input type="text" name="answers[{{ $question->id }}][1]" value="{{ $draft[1] ?? '' }}" class="w-full px-5 py-4 bg-white border border-black/20 text-black" maxlength="255">
                        @endif
                    @elseif(in_array($question->field_type->value, ['radio','select']))
                        @foreach($question->options as $opt)
                            <label class="flex items-center gap-3 text-black">
                                <input type="radio" name="answers[{{ $question->id }}][1]" value="{{ $opt->id }}" {{ ($draft[1] ?? null) == $opt->id ? 'checked' : '' }}>
                                <span>{{ $opt->label }}</span>
                            </label>
                        @endforeach
                    @elseif($question->field_type->value === 'checkbox')
                        @foreach($question->options as $i => $opt)
                            <label class="flex items-center gap-3 text-black">
                                <input type="checkbox" name="answers[{{ $question->id }}][{{ $i }}]" value="{{ $opt->id }}" {{ in_array($opt->id, (array)($draft ?? [])) ? 'checked' : '' }}>
                                <span>{{ $opt->label }}</span>
                            </label>
                        @endforeach
                    @endif

                    <div class="flex flex-col sm:flex-row gap-4">
                        <button type="submit" name="direction" value="prev" class="muno-btn-secondary-outline flex-1">Poprzednie</button>
                        <button type="submit" name="direction" value="next" class="muno-btn-secondary flex-1">{{ $step < $total ? 'Następne' : 'Podsumowanie' }}</button>
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
