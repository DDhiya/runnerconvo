@php
    // 16px (text-base) on purpose: iOS Safari zooms into any input under 16px, and most
    // registrants arrive from the WhatsApp or Instagram in-app browser on a phone.
    $input = 'mt-1.5 w-full rounded-xl bg-white/70 px-4 py-3 text-base text-ink ring-1 ring-black/10 focus:ring-2 focus:ring-brand-500 focus:outline-none';
    $invalid = fn (string $n) => $errors->has($n) ? 'aria-invalid="true" aria-describedby="'.$n.'-error"' : '';
    $legend = 'text-xs font-bold tracking-wide text-ink-muted uppercase';
    $delivery = old('delivery_method', 'pickup');
@endphp

<form method="POST" action="{{ route('register.store') }}" novalidate data-submit-once class="space-y-8">
    @csrf

    {{-- Bot checks, see RegistrationRequest::after(). The honeypot is off-screen rather than
         display:none (some bots skip hidden fields) and named so autofill never matches it. --}}
    <div aria-hidden="true" class="absolute -left-[9999px] h-0 w-0 overflow-hidden">
        <label>Leave this empty <input type="text" name="contact_me_by_fax_only" tabindex="-1" autocomplete="off"></label>
    </div>
    <input type="hidden" name="_started" value="{{ old('_started', Crypt::encryptString((string) now()->timestamp)) }}">

    <fieldset class="space-y-4">
        <legend class="{{ $legend }}">{{ __('register.sections.about') }}</legend>

        <x-field name="full_name" :label="__('register.fields.full_name.label')" :hint="__('register.fields.full_name.hint')">
            <input id="full_name" name="full_name" type="text" value="{{ old('full_name') }}" required
                   maxlength="120" autocomplete="name" class="{{ $input }}" {!! $invalid('full_name') !!}>
        </x-field>

        <x-field name="matric_no" :label="__('register.fields.matric_no.label')" :hint="__('register.fields.matric_no.hint')">
            <input id="matric_no" name="matric_no" type="text" value="{{ old('matric_no') }}" required
                   autocapitalize="characters" spellcheck="false" autocomplete="off" class="{{ $input }}" {!! $invalid('matric_no') !!}>
        </x-field>

        <x-field name="phone" :label="__('register.fields.phone.label')" :hint="__('register.fields.phone.hint')">
            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" required
                   inputmode="tel" autocomplete="tel" placeholder="012-345 6789" class="{{ $input }}" {!! $invalid('phone') !!}>
        </x-field>
    </fieldset>

    <fieldset class="space-y-4">
        <legend class="{{ $legend }}">{{ __('register.sections.robe') }}</legend>

        <x-field name="programme_level" :label="__('register.fields.programme_level.label')">
            <select id="programme_level" name="programme_level" required class="{{ $input }}" {!! $invalid('programme_level') !!}>
                <option value="">{{ __('register.choose') }}</option>
                @foreach (__('register.options.programme_level') as $code => $label)
                    <option value="{{ $code }}" @selected(old('programme_level') === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </x-field>

        @foreach (['faculty' => 'faculty_id', 'robe_size' => 'robe_size_id', 'convocation_session' => 'convocation_session_id'] as $type => $field)
            <x-field :name="$field" :label="__('register.fields.'.$field.'.label')">
                <select id="{{ $field }}" name="{{ $field }}" required class="{{ $input }}" {!! $invalid($field) !!}>
                    <option value="">{{ __('register.choose') }}</option>
                    @foreach ($options[$type] as $option)
                        <option value="{{ $option->id }}" @selected((string) old($field) === (string) $option->id)>{{ $option->label }}</option>
                    @endforeach
                </select>
            </x-field>
        @endforeach
    </fieldset>

    @include('register._delivery', ['input' => $input, 'invalid' => $invalid, 'legend' => $legend, 'delivery' => $delivery])

    <x-field name="notes" :label="__('register.fields.notes.label')" :hint="__('register.fields.notes.hint')">
        <textarea id="notes" name="notes" rows="3" maxlength="500" class="{{ $input }}" {!! $invalid('notes') !!}>{{ old('notes') }}</textarea>
    </x-field>

    <div class="space-y-3">
        <div>
            <label class="flex items-start gap-3 text-sm leading-relaxed text-ink-soft">
                <input id="documents_ack" type="checkbox" name="documents_ack" value="1" @checked(old('documents_ack'))
                       class="mt-1 h-5 w-5 shrink-0 rounded border-black/20 text-brand-600 focus:ring-brand-500" {!! $invalid('documents_ack') !!}>
                <span>{{ __('register.fields.documents_ack.label') }}</span>
            </label>
            @error('documents_ack')
                <p id="documents_ack-error" class="mt-1.5 text-sm font-medium text-brand-700">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="flex items-start gap-3 text-sm leading-relaxed text-ink-soft">
                <input id="consent" type="checkbox" name="consent" value="1" @checked(old('consent'))
                       class="mt-1 h-5 w-5 shrink-0 rounded border-black/20 text-brand-600 focus:ring-brand-500" {!! $invalid('consent') !!}>
                <span>{!! str_replace(
                    ':link',
                    '<a href="'.e(route('privacy')).'" target="_blank" rel="noopener" class="font-semibold text-brand-700 underline">'.e(__('register.fields.consent.link')).'</a>',
                    e(__('register.fields.consent.label')),
                ) !!}</span>
            </label>
            @error('consent')
                <p id="consent-error" class="mt-1.5 text-sm font-medium text-brand-700">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <button type="submit" class="btn-primary w-full">{{ __('register.submit') }}</button>
</form>
