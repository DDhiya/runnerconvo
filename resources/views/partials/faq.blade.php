{{-- Native <details>: keyboard accessible and screen-reader friendly with no JavaScript. --}}
<section id="faq" class="section">
    <div class="reveal mx-auto max-w-2xl text-center">
        <span class="eyebrow">{{ __('landing.faq.eyebrow') }}</span>
        <h2 class="section-title">{{ __('landing.faq.title') }}</h2>
    </div>

    <div class="reveal mx-auto mt-12 max-w-3xl space-y-3">
        @foreach (__('landing.faq.items') as $item)
            <details class="glass-card group px-6 py-1 [&[open]]:ring-brand-200">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 py-5 text-left font-semibold text-ink marker:content-['']">
                    {{ $item['q'] }}
                    <svg class="h-5 w-5 shrink-0 text-brand-600 transition-transform duration-200 group-open:rotate-45"
                         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" d="M12 5v14M5 12h14"/>
                    </svg>
                </summary>
                <p class="pb-5 text-sm leading-relaxed text-ink-soft">{{ $item['a'] }}</p>
            </details>
        @endforeach
    </div>
</section>
