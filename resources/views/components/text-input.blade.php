@props(['name', 'label' => null, 'type' => 'text', 'value' => '', 'placeholder' => '', 'required' => false])
<div>
    @if($label)
        <label for="{{ $name }}" class="block text-black mb-2 font-body">{{ $label }}</label>
    @endif
    <input type="{{ $type }}" name="{{ $name }}" id="{{ $name }}" value="{{ old($name, $value) }}"
        placeholder="{{ $placeholder }}" @if($required) required @endif
        {{ $attributes->merge(['class' => 'w-full px-5 py-4 bg-white border border-black/20 text-black placeholder:text-black/40 focus:outline-none focus:ring-2 focus:ring-[var(--munoludy-button-bg)] transition-all font-body']) }}>
    @error($name)<p class="mt-2 text-red-600 text-sm">{{ $message }}</p>@enderror
</div>
