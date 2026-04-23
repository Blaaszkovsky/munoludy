@props(['variant' => 'primary', 'type' => 'button'])
@php
$classes = match($variant) {
    'primary' => 'w-full py-4 rounded-2xl font-semibold text-lg bg-[var(--munoludy-button-bg)] text-[var(--munoludy-button-text)] font-heading transition-all duration-300 hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed',
    'outline' => 'w-full py-4 rounded-2xl font-semibold text-lg border-2 border-[var(--munoludy-button-bg)] text-[var(--munoludy-button-bg)] font-heading transition-all duration-300 hover:bg-white/5',
    default => 'w-full py-4 rounded-2xl font-semibold text-lg bg-[var(--munoludy-button-bg)] text-[var(--munoludy-button-text)] font-heading transition-all duration-300 hover:scale-[1.02] active:scale-[0.98]',
};
@endphp
<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
