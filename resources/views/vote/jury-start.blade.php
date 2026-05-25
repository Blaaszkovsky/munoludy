<x-layouts.app :title="$content['title']">
    <x-header :title="$content['title']" />
    <main class="flex-1 px-8 py-12 md:px-16 flex items-center">
        <div class="max-w-3xl mx-auto w-full">
            <x-form-card>
                <div class="text-white/90 mb-8 text-sm md:text-base leading-relaxed space-y-4 font-body">
                    @foreach(($content['intro_paragraphs'] ?? []) as $p)
                        <p>{{ $p }}</p>
                    @endforeach
                    @if(!empty($content['signature_name']))
                        <p class="text-right italic mt-6">
                            {{ $content['signature_name'] }}<br>
                            <span class="text-sm text-white/70">{{ $content['signature_role'] ?? '' }}</span>
                        </p>
                    @endif
                </div>
                <form method="POST" action="{{ route('jury.vote.verify-email', ['hash' => $hash]) }}" class="space-y-6">
                    @csrf
                    <label class="block text-white mb-3 text-center text-lg font-heading">
                        {{ $content['email_label'] ?? 'Podaj swój adres e-mail' }}
                    </label>
                    <input type="email" name="email" required autocomplete="email"
                        placeholder="{{ $content['email_placeholder'] ?? 'jan.kowalski@example.com' }}"
                        value="{{ old('email') }}"
                        class="w-full px-6 py-4 rounded-2xl bg-white/10 border border-white/20 text-white text-center text-lg focus:outline-none focus:ring-2 focus:ring-[var(--munoludy-button-bg)]">
                    @error('email')<p class="text-red-300 text-sm text-center">{{ $message }}</p>@enderror
                    <x-btn type="submit">{{ $content['start_button'] ?? 'Rozpocznij głosowanie' }}</x-btn>
                </form>
            </x-form-card>
        </div>
    </main>
    <x-footer />
</x-layouts.app>
