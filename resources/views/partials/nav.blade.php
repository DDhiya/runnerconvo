@php
    $links = [
        '#how' => __('landing.nav.how'),
        '#pricing' => __('landing.nav.pricing'),
        '#pickup' => __('landing.nav.pickup'),
        '#faq' => __('landing.nav.faq'),
    ];
    $locales = ['ms' => 'BM', 'en' => 'EN'];
    $current = app()->getLocale();
@endphp

<header data-header
        class="sticky top-0 z-40 border-b border-white/40 bg-white/60 backdrop-blur-xl transition duration-300">
    <nav class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between gap-4 px-5 sm:px-8"
         aria-label="{{ config('app.name') }}">

        {{-- Wordmark. TODO: swap for a logo mark once you have one. --}}
        <a href="{{ route('home') }}" class="flex items-center gap-2 text-lg font-bold tracking-tight text-ink">
            <span class="grid h-8 w-8 place-items-center rounded-xl bg-gradient-to-br from-brand-600 to-accent-500 text-sm font-black text-white shadow-md shadow-brand-500/25">J</span>
            <span>Jubah<span class="gradient-text">Runner</span></span>
        </a>

        {{-- Desktop links --}}
        <ul class="hidden items-center gap-7 text-sm font-medium text-ink-soft md:flex">
            @foreach ($links as $href => $label)
                <li><a href="{{ $href }}" class="transition hover:text-brand-700">{{ $label }}</a></li>
            @endforeach
        </ul>

        <div class="flex items-center gap-2">
            {{-- Language toggle: plain links, so switching works without JavaScript. --}}
            <div class="flex items-center rounded-full bg-white/70 p-0.5 ring-1 ring-black/5"
                 role="group" aria-label="{{ __('landing.nav.lang') }}">
                @foreach ($locales as $code => $label)
                    <a href="{{ route('locale.switch', $code) }}"
                       @if ($code === $current) aria-current="true" @endif
                       class="rounded-full px-3 py-1.5 text-xs font-bold transition
                              {{ $code === $current
                                  ? 'bg-gradient-to-r from-brand-600 to-accent-500 text-white shadow-sm'
                                  : 'text-ink-muted hover:text-brand-700' }}">
                        <span class="sr-only">{{ __('landing.nav.lang') }}: </span>{{ $label }}
                    </a>
                @endforeach
            </div>

            <a href="{{ config('jubahrunner.register_url') }}"
               class="btn-primary hidden !px-5 !py-2.5 sm:inline-flex">{{ __('landing.nav.cta') }}</a>

            <button type="button" data-menu-toggle aria-expanded="false" aria-controls="mobile-menu"
                    class="grid h-10 w-10 place-items-center rounded-xl bg-white/70 ring-1 ring-black/5 md:hidden">
                <span class="sr-only">{{ __('landing.nav.menu') }}</span>
                <svg class="h-5 w-5 text-ink" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
                </svg>
            </button>
        </div>
    </nav>

    {{-- Mobile sheet --}}
    <div id="mobile-menu" data-menu-panel hidden
         class="border-t border-white/50 bg-white/90 backdrop-blur-xl md:hidden">
        <ul class="mx-auto flex w-full max-w-6xl flex-col gap-1 px-5 py-4 text-base font-medium text-ink sm:px-8">
            @foreach ($links as $href => $label)
                <li><a href="{{ $href }}" class="block rounded-xl px-3 py-3 transition hover:bg-brand-50">{{ $label }}</a></li>
            @endforeach
            <li class="pt-2">
                <a href="{{ config('jubahrunner.register_url') }}" class="btn-primary w-full">{{ __('landing.nav.cta') }}</a>
            </li>
        </ul>
    </div>
</header>
