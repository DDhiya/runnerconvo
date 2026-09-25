@props(['status'])

{{-- Booking status pill for the admin. $status is an App\Enums\BookingStatus. --}}
@php
    $classes = match ($status->value) {
        'submitted' => 'bg-black/5 text-ink-soft ring-black/10',
        'confirmed' => 'bg-brand-50 text-brand-700 ring-brand-100',
        'collected' => 'bg-accent-100 text-accent-600 ring-accent-300/50',
        'handed_over' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        default => 'bg-black/5 text-ink-muted ring-black/10 line-through',
    };
@endphp
<span {{ $attributes->class(['rounded-full px-2.5 py-1 text-[11px] font-bold tracking-wide whitespace-nowrap uppercase ring-1', $classes]) }}>{{ $status->label() }}</span>
