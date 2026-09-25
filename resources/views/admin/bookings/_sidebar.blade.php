@php
    // The first message to the graduate is in the language they registered in.
    $greeting = __('register.admin_greeting', ['name' => $booking->full_name, 'reference' => $booking->reference], $booking->locale);
    $history = [
        'Registered' => $booking->created_at,
        'Confirmed' => $booking->confirmed_at,
        'Collected' => $booking->collected_at,
        'Handed over' => $booking->handed_over_at,
        'Cancelled' => $booking->cancelled_at,
    ];
@endphp

<aside class="space-y-4">
    <a href="{{ $booking->whatsapp_url.'?text='.rawurlencode($greeting) }}" target="_blank" rel="noopener noreferrer"
       class="btn-primary w-full">
        <x-whatsapp-icon class="h-4 w-4" />
        WhatsApp {{ Str::before($booking->full_name, ' ') }}
    </a>

    <div class="glass-card space-y-3 p-5 text-sm">
        <h2 class="{{ $heading }}">History</h2>
        <dl class="space-y-2 text-ink-soft">
            @foreach ($history as $label => $at)
                @if ($at)
                    <div class="flex justify-between gap-3"><dt>{{ $label }}</dt><dd class="text-right">{{ $at->timezone($tz)->format('j M Y, H:i') }}</dd></div>
                @endif
            @endforeach
            <div class="flex justify-between gap-3"><dt>Language</dt><dd>{{ strtoupper($booking->locale) }}</dd></div>
            <div class="flex justify-between gap-3">
                <dt>Consent</dt>
                <dd class="text-right">{{ $booking->consented_at->timezone($tz)->format('j M Y') }}<br><span class="text-xs text-ink-muted">notice {{ $booking->privacy_version }}</span></dd>
            </div>
        </dl>
    </div>

    <form method="POST" action="{{ route('admin.bookings.destroy', $booking) }}"
          onsubmit="return confirm('Permanently delete {{ $booking->reference }}? This erases the graduate data and cannot be undone.');">
        @csrf
        @method('DELETE')
        <button type="submit" class="w-full rounded-full px-4 py-2.5 text-sm font-semibold text-ink-muted ring-1 ring-black/10 transition hover:text-brand-700">
            Delete permanently (erasure request)
        </button>
    </form>
</aside>
