@extends('layouts.app')

@section('title', __('privacy.meta.title'))

@section('content')
    <section class="section">
        <div class="mx-auto max-w-3xl space-y-14">

            {{-- Both languages, current one first: PDPA s.7(3) wants the notice in the national
                 language AND English, and this does not depend on the language toggle. --}}
            @foreach ($locales as $locale)
                <article lang="{{ $locale }}" @if ($loop->first) id="notice" @endif>
                    <span class="eyebrow">{{ __('privacy.language', [], $locale) }}</span>
                    <h1 class="section-title">{{ __('privacy.title', [], $locale) }}</h1>
                    <p class="mt-2 text-xs text-ink-muted">{{ __('privacy.updated', ['version' => config('jubahrunner.privacy_version')], $locale) }}</p>
                    <p class="mt-4 leading-relaxed text-ink-soft">{{ __('privacy.intro', [], $locale) }}</p>

                    <div class="mt-8 space-y-6">
                        @foreach (__('privacy.sections', [], $locale) as $key => $section)
                            <div>
                                <h2 class="text-lg font-semibold text-ink">{{ $section['title'] }}</h2>
                                <p class="mt-1.5 text-sm leading-relaxed text-ink-soft">
                                    {{ __('privacy.sections.'.$key.'.body', [
                                        'whatsapp' => '+'.config('jubahrunner.whatsapp_number'),
                                        'email' => config('jubahrunner.email'),
                                    ], $locale) }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </article>
            @endforeach

        </div>
    </section>
@endsection
