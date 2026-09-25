@extends('layouts.app')

@section('title', __('register.meta.title'))

@section('content')
    <section class="section">
        <div class="mx-auto max-w-2xl">

            <div class="reveal text-center">
                <span class="eyebrow">{{ __('register.eyebrow') }}</span>
                <h1 class="section-title">{{ __('register.title') }}</h1>
                <p class="mt-4 leading-relaxed text-ink-soft">{{ __('register.subtitle') }}</p>
            </div>

            @if ($state === 'open')
                {{-- Error summary: focused by app.js so keyboard and screen-reader users land on it. --}}
                @if ($errors->any())
                    <div role="alert" tabindex="-1" data-error-summary
                         class="mt-8 rounded-2xl bg-brand-50 p-5 text-sm text-brand-800 ring-1 ring-brand-200">
                        <p class="font-bold">{{ __('register.errors.summary') }}</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->messages() as $field => $messages)
                                <li>
                                    @if ($field === 'form')
                                        {{ $messages[0] }}
                                    @else
                                        <a href="#{{ $field }}" class="underline">{{ $messages[0] }}</a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="glass-card mt-8 p-6 sm:p-8">
                    @include('register._form')
                </div>
            @else
                <div class="glass-card mt-10 p-8 text-center">
                    <h2 class="text-xl font-bold text-ink">
                        {{ $state === 'closed' ? __('register.closed.title') : __('register.soon.title') }}
                    </h2>
                    <p class="mt-3 leading-relaxed text-ink-soft">
                        @if ($state === 'closed')
                            {{ __('register.closed.body', ['date' => $closesAt->locale(app()->getLocale())->translatedFormat('j F Y')]) }}
                        @else
                            {{ __('register.soon.body') }}
                        @endif
                    </p>
                    <a href="{{ config('jubahrunner.whatsapp_url') }}" target="_blank" rel="noopener noreferrer"
                       class="btn-primary mt-6">
                        <x-whatsapp-icon class="h-4 w-4" />
                        {{ __('landing.cta.secondary') }}
                    </a>
                </div>
            @endif

        </div>
    </section>
@endsection
