{{--
    Shared by create.blade.php and edit.blade.php. Admin copy is hardcoded English
    here on purpose — lang/*/landing.php is the public page's copy and is tied to an
    en/ms key-parity check; doubling that surface with internal strings nobody will
    translate is a real cost for no benefit.
--}}
<div>
    <label for="name" class="block text-xs font-bold tracking-wide text-ink-muted uppercase">Name</label>
    <input id="name" type="text" name="name" value="{{ old('name', $runner->name) }}" required autofocus
           class="mt-1.5 w-full rounded-xl bg-white/70 px-4 py-2.5 text-sm text-ink ring-1 ring-black/10 focus:ring-2 focus:ring-brand-500 focus:outline-none">
    @error('name')
        <p class="mt-1.5 text-xs text-brand-700">{{ $message }}</p>
    @enderror
</div>

<div class="mt-4">
    <label for="phone" class="block text-xs font-bold tracking-wide text-ink-muted uppercase">WhatsApp number</label>
    <input id="phone" type="text" name="phone" value="{{ old('phone', $runner->phone) }}" required
           placeholder="e.g. 012-345 6789 or +60123456789"
           class="mt-1.5 w-full rounded-xl bg-white/70 px-4 py-2.5 text-sm text-ink ring-1 ring-black/10 focus:ring-2 focus:ring-brand-500 focus:outline-none">
    @error('phone')
        <p class="mt-1.5 text-xs text-brand-700">{{ $message }}</p>
    @enderror
</div>

<div class="mt-4">
    {{-- Hidden field first: an unchecked checkbox submits nothing at all, and
         `is_active` is validated as required|boolean. --}}
    <input type="hidden" name="is_active" value="0">
    <label class="flex items-center gap-2 text-sm text-ink-soft">
        <input type="checkbox" name="is_active" value="1"
               @checked(old('is_active', $runner->is_active))
               class="rounded border-black/20 text-brand-600 focus:ring-brand-500">
        Active (shown on the public site)
    </label>
</div>
