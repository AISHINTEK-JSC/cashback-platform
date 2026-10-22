@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'hint' => null])

<div>
    @if ($label)
        <label for="{{ $name }}" class="label">{{ $label }}</label>
    @endif
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $type === 'password' ? '' : old($name, $value) }}" {{ $attributes->class(['input', 'border-red-400' => $errors->has($name)]) }}>
    @if ($hint)
        <p class="mt-1.5 text-xs text-stone-500">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="field-error">{{ $message }}</p>
    @enderror
</div>
