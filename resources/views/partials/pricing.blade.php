<section id="pricing" class="section">
    <div class="reveal mx-auto max-w-2xl text-center">
        <span class="eyebrow">{{ __('landing.pricing.eyebrow') }}</span>
        <h2 class="section-title">{{ __('landing.pricing.title') }}</h2>
        <p class="mt-4 text-lg text-ink-soft">{{ __('landing.pricing.subtitle') }}</p>
    </div>

    <div class="reveal mx-auto mt-12 max-w-lg">
        <div class="glass-card overflow-hidden">

            <div class="bg-gradient-to-br from-brand-600 to-accent-500 px-8 py-10 text-center text-white">
                <p class="text-sm font-semibold tracking-wide uppercase opacity-90">
                    {{ __('landing.pricing.plan') }}
                </p>
                <p class="mt-3 text-5xl font-extrabold tracking-tight">
                    {{-- TODO: confirm the real price, then update lang/{en,ms}/landing.php -> pricing.price --}}
                    {{ __('landing.pricing.price') }}
                </p>
                <p class="mt-1 text-sm opacity-90">{{ __('landing.pricing.note') }}</p>
            </div>

            <div class="px-8 py-8">
                <ul class="space-y-4">
                    @foreach (__('landing.pricing.includes') as $item)
                        <li class="flex items-start gap-3 text-sm text-ink-soft">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-brand-600" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/>
                            </svg>
                            <span>{{ $item }}</span>
                        </li>
                    @endforeach
                </ul>

                <a href="{{ config('jubahrunner.register_url') }}" class="btn-primary mt-8 w-full">
                    {{ __('landing.pricing.cta') }}
                </a>

                <p class="mt-4 text-center text-xs text-ink-muted">{{ __('landing.pricing.footnote') }}</p>
            </div>
        </div>
    </div>
</section>
