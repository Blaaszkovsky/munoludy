<x-layouts.app :title="$content['hero_title'] ?? 'Munoludy'">
    <x-header :title="$content['hero_title']" :poweredBy="$content['hero_powered_by']" />
    <main class="flex-1 px-8 py-12 md:px-16">
        <div class="max-w-4xl mx-auto">
            <p class="text-black text-base md:text-lg leading-relaxed mb-16 font-body">{{ $content['intro'] }}</p>
            @if(session('registered_email'))
                <x-form-card class="text-center">
                    <h2 class="text-3xl md:text-4xl mb-4 font-heading text-[var(--munoludy-text)]">{{ $content['success_title'] }}</h2>
                    <p class="text-white/80 text-base md:text-lg font-body">
                        {!! str_replace(':email', '<strong>'.e(session('registered_email')).'</strong>', $content['success_text']) !!}
                    </p>
                    <p class="text-white/60 text-sm font-body mt-6">
                        {{ $content['success_hint'] ?? 'Nie widzisz maila? Sprawdź folder SPAM.' }}
                    </p>
                </x-form-card>
            @else
                <x-form-card>
                    <h2 class="text-3xl md:text-4xl mb-4 font-heading text-[var(--munoludy-text)]">{{ $content['form_title'] }}</h2>
                    <p class="text-white/80 mb-8 text-sm md:text-base font-body">{{ $content['form_subtitle'] }}</p>
                    <form method="POST" action="{{ route('register') }}" class="space-y-6">
                        @csrf
                        <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off">
                        <input type="hidden" name="render_ts" value="{{ $renderTs }}">
                        <x-text-input name="email" type="email" :label="$content['form_email_label']" :placeholder="$content['form_email_placeholder']" required />
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <input type="checkbox" id="privacy_consent" name="privacy_consent" value="1" required class="mt-1 w-5 h-5 rounded border-white/30 bg-white/10 text-[var(--munoludy-button-bg)]">
                                <label for="privacy_consent" class="text-white/90 text-sm cursor-pointer [&_a]:underline [&_a:hover]:text-white">{!! $content['form_privacy_label'] !!}</label>
                            </div>
                            <div class="flex items-start gap-3">
                                <input type="checkbox" id="marketing_consent" name="marketing_consent" value="1" class="mt-1 w-5 h-5 rounded border-white/30 bg-white/10">
                                <label for="marketing_consent" class="text-white/90 text-sm cursor-pointer">{{ $content['form_marketing_label'] }}</label>
                            </div>
                        </div>
                        @if(config('munoludy.turnstile.site_key'))
                            <div class="cf-turnstile" data-sitekey="{{ config('munoludy.turnstile.site_key') }}"></div>
                            @push('scripts')
                                <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
                            @endpush
                        @endif
                        <x-btn type="submit">{{ $content['form_submit_label'] }}</x-btn>
                        <div class="mt-6 p-4 bg-white/5 rounded-xl border border-white/10">
                            <p class="text-white/70 text-xs leading-relaxed"><strong class="text-white/90">RODO:</strong> {{ \App\Models\SiteSetting::get('rodo_admin', $content['rodo']) }}</p>
                        </div>
                    </form>
                </x-form-card>
            @endif
        </div>
    </main>
    <x-footer />
</x-layouts.app>
