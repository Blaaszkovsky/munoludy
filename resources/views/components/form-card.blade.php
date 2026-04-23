@props([])
<div {{ $attributes->merge(['class' => 'bg-white/10 backdrop-blur-md rounded-3xl p-8 md:p-12 border border-white/20 shadow-2xl']) }}>
    {{ $slot }}
</div>
