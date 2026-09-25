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
                <a href="{{ $registerUrl }}"
                   class="btn w-full bg-white text-brand-700 shadow-lg hover:-translate-y-0.5 sm:w-auto">
                    {{ __('landing.cta.primary') }}
                </a>
                <a href="{{ config('jubahrunner.whatsapp_url') }}" target="_blank" rel="noopener noreferrer"
                   class="btn w-full bg-white/15 text-white ring-1 ring-white/30 backdrop-blur hover:bg-white/25 sm:w-auto">
                    <x-whatsapp-icon class="h-4 w-4" />
                    {{ __('landing.cta.secondary') }}
                </a>
            </div>
        </div>
    </div>
</section>
