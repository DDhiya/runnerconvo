{{-- Team-facing, so hardcoded English like the rest of the admin. Every typed value goes
     through $md (App\Support\MarkdownText): a graduate's name must never become a link. --}}
@php
    $md = fn (?string $value) => \App\Support\MarkdownText::escape($value);
@endphp
<x-mail::message>
# New booking {{ $booking->reference }}

<x-mail::table>
| | |
|:--|:--|
| Name | {{ $md($booking->full_name) }} |
| Matric | {{ $md($booking->matric_no) }} ({{ __('register.options.programme_level.'.$booking->programme_level, [], 'en') }}) |
| WhatsApp | [+{{ $booking->phone }}]({{ $booking->whatsapp_url }}) |
| Email | {{ $booking->email ? $md($booking->email) : '-' }} |
| Faculty | {{ $md($booking->faculty->label_en) }} |
| Robe size | {{ $md($booking->robeSize->label_en) }} |
| Session | {{ $md($booking->convocationSession->label_en) }} |
| Delivery | {{ $booking->isCod() ? 'Kuantan COD' : 'Self-pickup' }} |
@if ($booking->isCod())
| Address | {{ $md($booking->delivery_address) }} |
@endif
| Language | {{ strtoupper($booking->locale) }} |
</x-mail::table>

@if ($booking->notes)
**Note from the graduate:** {{ $md($booking->notes) }}
@endif

<x-mail::button :url="route('admin.bookings.show', $booking)">
Open in admin
</x-mail::button>

Next: WhatsApp them to confirm details, documents and payment, then assign a runner.
</x-mail::message>
