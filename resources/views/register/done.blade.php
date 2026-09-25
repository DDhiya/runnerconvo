@extends('layouts.app')

@section('title', __('register.done.title').' - '.config('app.name'))

{{-- A confirmation page carries a reference and a name: keep it out of search results. --}}
@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    @php
        $message = __('register.done.whatsapp_message', ['name' => $booking->full_name, 'reference' => $booking->reference]);
        $whatsapp = config('jubahrunner.whatsapp_url').'?text='.rawurlencode($message);
    @endphp

    <section class="section">
        <div class="mx-auto max-w-2xl">

            <div class="reveal text-center">
                <span class="eyebrow">{{ __('register.done.title') }}</span>
                <h1 class="section-title">{{ __('register.done.subtitle', ['name' => $booking->full_name]) }}</h1>
            </div>

            <div class="glass-card mt-8 p-6 text-center sm:p-8">
                <p class="text-xs font-bold tracking-wide text-ink-muted uppercase">{{ __('register.done.reference_label') }}</p>
                <p class="mt-2 text-3xl font-extrabold tracking-wider text-brand-700 select-all">{{ $booking->reference }}</p>
                @if ($booking->email)
                    <p class="mt-2 text-sm text-ink-soft">{{ __('register.done.emailed', ['email' => $booking->email]) }}</p>
                @endif

                <dl class="mt-6 grid gap-4 text-left sm:grid-cols-2">
                    <div class="rounded-xl bg-white/70 p-4 ring-1 ring-black/5">
                        <dt class="text-xs font-bold tracking-wide text-ink-muted uppercase">{{ __('register.done.session_label') }}</dt>
                        <dd class="mt-1 font-semibold text-ink">{{ $booking->convocationSession->label }}</dd>
                    </div>
                    <div class="rounded-xl bg-white/70 p-4 ring-1 ring-black/5">
                        <dt class="text-xs font-bold tracking-wide text-ink-muted uppercase">{{ __('register.done.delivery_label') }}</dt>
                        <dd class="mt-1 font-semibold text-ink">{{ __('register.delivery.'.$booking->delivery_method.'.title') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="mt-8">
                <h2 class="text-lg font-semibold text-ink">{{ __('register.done.next_title') }}</h2>
                <ol class="mt-4 space-y-3 text-sm leading-relaxed text-ink-soft">
                    @foreach (__('register.done.next') as $i => $step)
                        <li class="flex gap-3">
                            <span aria-hidden="true" class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-gradient-to-br from-brand-600 to-accent-500 text-xs font-bold text-white">{{ $i + 1 }}</span>
                            <span class="pt-0.5">{{ $step }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>

            <div class="mt-10 flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
                <a href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="btn-primary w-full sm:w-auto">
                    <x-whatsapp-icon class="h-4 w-4" />
                    {{ __('register.done.whatsapp_cta') }}
                </a>
                <a href="{{ route('home') }}" class="btn-ghost w-full sm:w-auto">{{ __('register.done.back') }}</a>
            </div>

        </div>
    </section>
@endsection
