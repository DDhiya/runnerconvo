<section class="relative">
    <div class="mx-auto w-full max-w-6xl px-5 pt-16 pb-20 sm:px-8 sm:pt-24 sm:pb-28">
        <div class="reveal mx-auto max-w-3xl text-center">

            <span class="eyebrow">
                <span class="h-1.5 w-1.5 rounded-full bg-accent-500"></span>
                {{ __('landing.hero.badge') }}
            </span>

            <h1 class="mt-6 text-4xl font-extrabold tracking-tight sm:text-6xl">
                {{ __('landing.hero.title') }}
                {{-- pb-2 keeps descenders (g, y) from being clipped by bg-clip-text. --}}
                <span class="gradient-text block pb-2 leading-[1.15]">{{ __('landing.hero.accent') }}</span>
            </h1>

            <p class="mx-auto mt-6 max-w-2xl text-lg text-ink-soft">
                {{ __('landing.hero.subtitle') }}
            </p>

            <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ config('jubahrunner.register_url') }}" class="btn-primary w-full sm:w-auto">
                    {{ __('landing.hero.primary') }}
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/>
                    </svg>
                </a>
                <a href="#how" class="btn-ghost w-full sm:w-auto">{{ __('landing.hero.secondary') }}</a>
            </div>

            <ul class="mt-10 flex flex-wrap items-center justify-center gap-x-6 gap-y-3 text-sm text-ink-muted">
                @foreach (__('landing.hero.chips') as $chip)
                    <li class="flex items-center gap-2">
                        <svg class="h-4 w-4 shrink-0 text-brand-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/>
                        </svg>
                        {{ $chip }}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</section>
