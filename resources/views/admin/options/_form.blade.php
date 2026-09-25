<div>
    <label for="label_en" class="block text-xs font-bold tracking-wide text-ink-muted uppercase">English label</label>
    <input id="label_en" type="text" name="label_en" value="{{ old('label_en', $option->label_en) }}" required autofocus maxlength="120"
           class="mt-1.5 w-full rounded-xl bg-white/70 px-4 py-2.5 text-sm text-ink ring-1 ring-black/10 focus:ring-2 focus:ring-brand-500 focus:outline-none">
    @error('label_en')
        <p class="mt-1.5 text-xs text-brand-700">{{ $message }}</p>
    @enderror
</div>

<div class="mt-4">
    <label for="label_ms" class="block text-xs font-bold tracking-wide text-ink-muted uppercase">Malay label</label>
    <input id="label_ms" type="text" name="label_ms" value="{{ old('label_ms', $option->label_ms) }}" required maxlength="120"
           class="mt-1.5 w-full rounded-xl bg-white/70 px-4 py-2.5 text-sm text-ink ring-1 ring-black/10 focus:ring-2 focus:ring-brand-500 focus:outline-none">
    @error('label_ms')
        <p class="mt-1.5 text-xs text-brand-700">{{ $message }}</p>
    @enderror
</div>

<div class="mt-4">
    {{-- Hidden field first: an unchecked checkbox submits nothing at all. --}}
    <input type="hidden" name="is_active" value="0">
    <label class="flex items-center gap-2 text-sm text-ink-soft">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $option->is_active ?? true))
               class="rounded border-black/20 text-brand-600 focus:ring-brand-500">
        Active (offered on the registration form)
    </label>
</div>
