@php
    // Every typed value goes through $md: see App\Support\MarkdownText.
    $md = fn (?string $value) => \App\Support\MarkdownText::escape($value);
@endphp
<x-mail::message>
# {{ __('mail.confirmation.greeting', ['name' => $md($booking->full_name)]) }}

{{ __('mail.confirmation.intro') }}

<x-mail::panel>
{{ __('mail.confirmation.reference') }}: **{{ $booking->reference }}**
</x-mail::panel>

## {{ __('mail.confirmation.details') }}

<x-mail::table>
| | |
|:--|:--|
| {{ __('mail.confirmation.matric') }} | {{ $md($booking->matric_no) }} |
| {{ __('mail.confirmation.session') }} | {{ $md($booking->convocationSession->label) }} |
| {{ __('mail.confirmation.robe_size') }} | {{ $md($booking->robeSize->label) }} |
| {{ __('mail.confirmation.delivery') }} | {{ __('register.delivery.'.$booking->delivery_method.'.title') }} |
</x-mail::table>

## {{ __('mail.confirmation.next_title') }}

@foreach (__('register.done.next') as $i => $step)
{{ $i + 1 }}. {{ $step }}
@endforeach

<x-mail::button :url="$whatsappUrl" color="success">
{{ __('mail.confirmation.whatsapp_cta') }}
</x-mail::button>

<x-mail::subcopy>
{{ __('mail.confirmation.footer') }}
</x-mail::subcopy>
</x-mail::message>
