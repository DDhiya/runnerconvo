@php
    $links = [
        '#how' => __('landing.nav.how'),
        '#pricing' => __('landing.nav.pricing'),
        '#pickup' => __('landing.nav.pickup'),
        '#faq' => __('landing.nav.faq'),
    ];
@endphp

<footer class="border-t border-white/50 bg-white/50 backdrop-blur-xl">
    <div class="mx-auto w-full max-w-6xl px-5 py-14 sm:px-8">

        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">

            <div class="lg:col-span-2">
                <span class="flex items-center gap-2.5 text-lg font-bold tracking-tight text-ink">
                    <img src="{{ asset('images/logo-mark-64.png') }}" alt="{{ config('app.name') }}" width="36" height="36" class="h-9 w-9">
                    <span aria-hidden="true">
                        <span class="text-brand-600">Jubah</span><span class="text-accent-400">Panda</span>
                    </span>
                </span>
                <p class="mt-4 max-w-sm text-sm leading-relaxed text-ink-soft">
                    {{ __('landing.footer.tagline') }}
                </p>
            </div>

            <div>
                <h2 class="text-xs font-bold tracking-wide text-ink uppercase">{{ __('landing.footer.explore') }}</h2>
                <ul class="mt-4 space-y-2.5 text-sm text-ink-soft">
                    @foreach ($links as $href => $label)
                        <li><a href="{{ $href }}" class="transition hover:text-brand-700">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h2 class="text-xs font-bold tracking-wide text-ink uppercase">{{ __('landing.footer.contact') }}</h2>
                {{-- TODO: real Instagram handle and email live in .env (JP_*). --}}
                <ul class="mt-4 space-y-2.5 text-sm text-ink-soft">
                    <li>
                        <a href="{{ config('jubahrunner.whatsapp_url') }}" target="_blank" rel="noopener noreferrer"
                           class="transition hover:text-brand-700">WhatsApp</a>
                    </li>
                    <li>
                        <a href="{{ config('jubahrunner.instagram_url') }}" target="_blank" rel="noopener noreferrer"
                           class="transition hover:text-brand-700">&#64;{{ config('jubahrunner.instagram') }}</a>
                    </li>
                    <li>
                        <a href="mailto:{{ config('jubahrunner.email') }}" class="transition hover:text-brand-700">
                            {{ config('jubahrunner.email') }}
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="mt-12 border-t border-black/5 pt-8">
            <p class="text-xs leading-relaxed text-ink-muted">{{ __('landing.footer.disclaimer') }}</p>
            <p class="mt-3 text-xs text-ink-muted">
                &copy; {{ now()->year }} {{ config('app.name') }}. {{ __('landing.footer.rights') }}
            </p>
        </div>
    </div>
</footer>
