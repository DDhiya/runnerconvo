<fieldset class="space-y-4">
    <legend class="{{ $legend }}">{{ __('register.sections.delivery') }}</legend>

    <div id="delivery_method" class="grid gap-4 sm:grid-cols-2" role="radiogroup" aria-label="{{ __('register.fields.delivery_method.label') }}">
        @foreach (['pickup' => 'self_badge', 'cod' => 'cod_badge'] as $method => $badge)
            <label class="block cursor-pointer">
                <input type="radio" name="delivery_method" value="{{ $method }}" class="peer sr-only"
                       data-delivery-radio @checked($delivery === $method)>
                <span class="glass-card block h-full p-5 transition peer-checked:ring-2 peer-checked:ring-brand-500 peer-focus-visible:outline-2 peer-focus-visible:outline-brand-600">
                    <span class="rounded-full px-2.5 py-1 text-[11px] font-bold tracking-wide uppercase ring-1
                                 {{ $method === 'pickup' ? 'bg-brand-50 text-brand-700 ring-brand-100' : 'bg-accent-100 text-accent-600 ring-accent-300/50' }}">
                        {{ __('landing.pickup.'.$badge) }}
                    </span>
                    <span class="mt-3 block text-base font-semibold text-ink">{{ __('register.delivery.'.$method.'.title') }}</span>
                    <span class="mt-1 block text-sm leading-relaxed text-ink-soft">{{ __('register.delivery.'.$method.'.body') }}</span>
                </span>
            </label>
        @endforeach
    </div>
    @error('delivery_method')
        <p class="text-sm font-medium text-brand-700">{{ $message }}</p>
    @enderror

    {{-- Always visible without JS; app.js hides it unless cash-on-delivery is picked. --}}
    <div data-delivery-address class="space-y-3">
        <p class="rounded-xl bg-brand-50/70 px-4 py-3 text-sm text-brand-700 ring-1 ring-brand-100">{{ __('landing.pickup.note') }}</p>
        <x-field name="delivery_address" :label="__('register.fields.delivery_address.label')" :hint="__('register.fields.delivery_address.hint')">
            <textarea id="delivery_address" name="delivery_address" rows="3" maxlength="300"
                      autocomplete="street-address" class="{{ $input }}" {!! $invalid('delivery_address') !!}>{{ old('delivery_address') }}</textarea>
        </x-field>
    </div>
</fieldset>
