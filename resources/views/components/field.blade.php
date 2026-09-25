@props(['name', 'label', 'hint' => null])

{{-- One labelled form control: label, the control itself (slot), an optional hint and the
     field error. The error id matches the aria-describedby the controls set. --}}
<div {{ $attributes }}>
    <label for="{{ $name }}" class="block text-sm font-semibold text-ink">{{ $label }}</label>
    {{ $slot }}
    @if ($hint)
        <p id="{{ $name }}-hint" class="mt-1.5 text-xs text-ink-muted">{{ $hint }}</p>
    @endif
    @error($name)
        <p id="{{ $name }}-error" class="mt-1.5 text-sm font-medium text-brand-700">{{ $message }}</p>
    @enderror
</div>
