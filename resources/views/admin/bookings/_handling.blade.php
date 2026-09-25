<div class="space-y-4">
    <h2 class="{{ $heading }}">Handling</h2>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-field name="status" label="Status">
            <select id="status" name="status" class="{{ $input }}">
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $booking->status->value) === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </x-field>
        <x-field name="runner_id" label="Runner">
            <select id="runner_id" name="runner_id" class="{{ $input }}">
                <option value="">Unassigned</option>
                @foreach ($runners as $runner)
                    <option value="{{ $runner->id }}" @selected((int) old('runner_id', $booking->runner_id) === $runner->id)>{{ $runner->name }}@unless ($runner->is_active) (inactive)@endunless</option>
                @endforeach
            </select>
        </x-field>
    </div>

    <x-field name="admin_notes" label="Internal notes (never shown to the graduate)">
        <textarea id="admin_notes" name="admin_notes" rows="3" class="{{ $input }}">{{ old('admin_notes', $booking->admin_notes) }}</textarea>
    </x-field>
</div>

<div class="space-y-4">
    <h2 class="{{ $heading }}">Payment</h2>

    <x-field name="amount" label="Amount (RM)">
        <input id="amount" name="amount" type="number" step="0.01" min="0"
               value="{{ old('amount', number_format($booking->amount_sen / 100, 2, '.', '')) }}" class="{{ $input }}">
    </x-field>

    {{-- Hidden field first: an unchecked checkbox submits nothing at all. --}}
    <input type="hidden" name="paid" value="0">
    <label class="flex items-center gap-2 text-sm text-ink-soft">
        <input type="checkbox" name="paid" value="1" @checked(old('paid', $booking->isPaid()))
               class="rounded border-black/20 text-brand-600 focus:ring-brand-500">
        Paid
        @if ($booking->paid_at)
            <span class="text-ink-muted">(since {{ $booking->paid_at->timezone($tz)->format('j M Y, H:i') }})</span>
        @endif
    </label>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-field name="payment_method" label="Method">
            <select id="payment_method" name="payment_method" class="{{ $input }}">
                <option value="">-</option>
                @foreach (['transfer' => 'Bank transfer', 'duitnow' => 'DuitNow', 'cash' => 'Cash', 'other' => 'Other'] as $code => $label)
                    <option value="{{ $code }}" @selected(old('payment_method', $booking->payment_method) === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </x-field>
        <x-field name="payment_reference" label="Reference / receipt no.">
            <input id="payment_reference" name="payment_reference" type="text" maxlength="100"
                   value="{{ old('payment_reference', $booking->payment_reference) }}" class="{{ $input }}">
        </x-field>
    </div>
</div>
