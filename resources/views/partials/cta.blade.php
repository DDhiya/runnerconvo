<section class="px-5 pb-24 sm:px-8">
    <div class="reveal relative mx-auto max-w-6xl overflow-hidden rounded-3xl bg-gradient-to-br from-brand-600 via-brand-500 to-accent-500 px-8 py-16 text-center shadow-xl shadow-brand-500/20 sm:px-16">

        <div aria-hidden="true" class="pointer-events-none absolute inset-0">
            <div class="absolute -top-24 -left-16 h-72 w-72 rounded-full bg-white/15 blur-3xl"></div>
            <div class="absolute -right-16 -bottom-24 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
        </div>

        <div class="relative">
            <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                {{ __('landing.cta.title') }}
            </h2>
            <p class="mx-auto mt-4 max-w-xl text-white/90">{{ __('landing.cta.body') }}</p>

            <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ config('jubahrunner.register_url') }}"
                   class="btn w-full bg-white text-brand-700 shadow-lg hover:-translate-y-0.5 sm:w-auto">
                    {{ __('landing.cta.primary') }}
                </a>
                <a href="{{ config('jubahrunner.whatsapp_url') }}" target="_blank" rel="noopener noreferrer"
                   class="btn w-full bg-white/15 text-white ring-1 ring-white/30 backdrop-blur hover:bg-white/25 sm:w-auto">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2Zm5.8 14.1c-.25.69-1.45 1.32-2 1.4-.51.08-1.16.11-1.87-.12-.43-.14-.98-.32-1.69-.63-2.97-1.28-4.91-4.27-5.06-4.47-.15-.2-1.21-1.61-1.21-3.07 0-1.46.77-2.18 1.04-2.48.27-.3.59-.37.79-.37.2 0 .39 0 .57.01.18.01.42-.07.66.5.25.59.84 2.05.91 2.2.07.15.12.32.02.52-.1.2-.15.32-.3.5-.15.17-.31.39-.44.52-.15.15-.3.31-.13.61.17.3.76 1.25 1.63 2.03 1.12 1 2.06 1.31 2.36 1.46.3.15.47.12.65-.07.17-.2.75-.87.95-1.17.2-.3.4-.25.66-.15.27.1 1.71.81 2 .96.3.15.5.22.57.35.07.12.07.72-.18 1.41Z"/>
                    </svg>
                    {{ __('landing.cta.secondary') }}
                </a>
            </div>
        </div>
    </div>
</section>
