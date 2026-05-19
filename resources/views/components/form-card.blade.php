@props([])
<div {{ $attributes->merge(['class' => 'muno-card']) }}>
    {{ $slot }}
</div>
