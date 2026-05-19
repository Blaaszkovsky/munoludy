@props(['variant' => 'primary', 'type' => 'button'])
@php
$classes = match($variant) {
    'primary' => 'muno-btn-primary',
    'secondary' => 'muno-btn-secondary',
    'outline' => 'muno-btn-secondary-outline',
    default => 'muno-btn-primary',
};
@endphp
<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
