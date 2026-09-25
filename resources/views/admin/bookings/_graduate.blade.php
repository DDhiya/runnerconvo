<div class="space-y-4">
    <h2 class="{{ $heading }}">Graduate</h2>

    <x-field name="full_name" label="Full name">
        <input id="full_name" name="full_name" type="text" value="{{ old('full_name', $booking->full_name) }}" required class="{{ $input }}">
    </x-field>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-field name="matric_no" label="Matric number">
            <input id="matric_no" name="matric_no" type="text" value="{{ old('matric_no', $booking->matric_no) }}" required class="{{ $input }}">
        </x-field>
        <x-field name="phone" label="WhatsApp number">
            <input id="phone" name="phone" type="text" value="{{ old('phone', $booking->phone) }}" required class="{{ $input }}">
        </x-field>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-field name="programme_level" label="Programme level">
            <select id="programme_level" name="programme_level" class="{{ $input }}">
                @foreach (__('register.options.programme_level', [], 'en') as $code => $label)
                    <option value="{{ $code }}" @selected(old('programme_level', $booking->programme_level) === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </x-field>
        <x-field name="faculty_id" label="Faculty">
            <select id="faculty_id" name="faculty_id" class="{{ $input }}">
                @foreach ($faculties as $option)
                    <option value="{{ $option->id }}" @selected((int) old('faculty_id', $booking->faculty_id) === $option->id)>{{ $option->label_en }}@unless ($option->is_active) (inactive)@endunless</option>
                @endforeach
            </select>
        </x-field>
        <x-field name="robe_size_id" label="Robe size">
            <select id="robe_size_id" name="robe_size_id" class="{{ $input }}">
                @foreach ($robeSizes as $option)
                    <option value="{{ $option->id }}" @selected((int) old('robe_size_id', $booking->robe_size_id) === $option->id)>{{ $option->label_en }}@unless ($option->is_active) (inactive)@endunless</option>
                @endforeach
            </select>
        </x-field>
        <x-field name="convocation_session_id" label="Convocation session">
            <select id="convocation_session_id" name="convocation_session_id" class="{{ $input }}">
                @foreach ($sessions as $option)
                    <option value="{{ $option->id }}" @selected((int) old('convocation_session_id', $booking->convocation_session_id) === $option->id)>{{ $option->label_en }}@unless ($option->is_active) (inactive)@endunless</option>
                @endforeach
            </select>
        </x-field>
    </div>

    <x-field name="delivery_method" label="Delivery">
        <select id="delivery_method" name="delivery_method" class="{{ $input }}">
            <option value="pickup" @selected(old('delivery_method', $booking->delivery_method) === 'pickup')>Self-pickup</option>
            <option value="cod" @selected(old('delivery_method', $booking->delivery_method) === 'cod')>Kuantan cash-on-delivery</option>
        </select>
    </x-field>

    <x-field name="delivery_address" label="Delivery address (COD)">
        <textarea id="delivery_address" name="delivery_address" rows="2" class="{{ $input }}">{{ old('delivery_address', $booking->delivery_address) }}</textarea>
    </x-field>

    <x-field name="notes" label="Graduate note">
        <textarea id="notes" name="notes" rows="2" class="{{ $input }}">{{ old('notes', $booking->notes) }}</textarea>
    </x-field>
</div>
