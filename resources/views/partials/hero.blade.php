<section class="relative overflow-hidden">
    <div class="mx-auto w-full max-w-6xl px-5 pt-16 pb-20 sm:px-8 sm:pt-24 sm:pb-28">
        <div class="grid items-center gap-14 lg:grid-cols-[1.05fr_0.95fr] lg:gap-10">

            {{-- Copy --}}
            <div class="reveal mx-auto max-w-2xl text-center lg:mx-0 lg:max-w-none lg:text-left">

                <span class="eyebrow">
                    <span class="h-1.5 w-1.5 rounded-full bg-accent-500"></span>
                    {{ __('landing.hero.badge') }}
                </span>

                <h1 class="mt-6 text-4xl font-extrabold tracking-tight sm:text-6xl">
                    {{ __('landing.hero.title') }}
                    {{-- pb-2 keeps descenders (g, y) from being clipped by bg-clip-text. --}}
                    <span class="gradient-text block pb-2 leading-[1.15]">{{ __('landing.hero.accent') }}</span>
                </h1>

                <p class="mx-auto mt-6 max-w-2xl text-lg text-ink-soft lg:mx-0">
                    {{ __('landing.hero.subtitle') }}
                </p>

                <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row lg:justify-start">
                    <a href="{{ $registerUrl }}" class="btn-primary w-full sm:w-auto">
                        {{ __('landing.hero.primary') }}
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/>
                        </svg>
                    </a>
                    <a href="#how" class="btn-ghost w-full sm:w-auto">{{ __('landing.hero.secondary') }}</a>
                </div>

                <ul class="mt-10 flex flex-wrap items-center justify-center gap-x-6 gap-y-3 text-sm text-ink-muted lg:justify-start">
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

            {{-- Mascot: the panda mark, floating over a soft blush blob, with two
                 stat badges pulled from copy that already exists elsewhere on the
                 page (no new translation keys needed). --}}
            <div class="reveal relative mx-auto w-full max-w-xs sm:max-w-sm lg:max-w-md" style="transition-delay: 120ms">
                <div aria-hidden="true"
                     class="absolute inset-0 -z-10 rounded-full bg-gradient-to-br from-brand-200/60 to-accent-300/50 blur-3xl"></div>

                <img src="{{ asset('images/logo-mark-512.png') }}"
                     alt="{{ config('app.name') }} mascot: a panda graduate in a cape"
                     class="animate-float mx-auto w-48 drop-shadow-xl sm:w-64 lg:w-72"
                     width="512" height="466" fetchpriority="high">

                <div class="badge-float top-2 -left-2 sm:top-6 sm:-left-6" style="animation-delay: -3s">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-brand-50 text-brand-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                    </span>
                    <p class="text-left text-xs font-semibold text-ink">{{ __('landing.hero.chips')[1] }}</p>
                </div>

                <div class="badge-float right-0 -bottom-2 sm:right-2 sm:-bottom-4">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-accent-100 text-xs font-extrabold text-accent-600">RM</span>
                    <div class="text-left">
                        <p class="text-sm leading-tight font-extrabold text-ink">{{ __('landing.pricing.price') }}</p>
                        <p class="text-[11px] leading-tight text-ink-muted">{{ __('landing.pricing.note') }}</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
