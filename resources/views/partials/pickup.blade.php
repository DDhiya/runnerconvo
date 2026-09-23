@php
    $schedule = [
        ['label' => __('landing.pickup.hours_label'), 'value' => __('landing.pickup.hours')],
        ['label' => __('landing.pickup.window_label'), 'value' => __('landing.pickup.window')],
    ];
@endphp

<section id="pickup" class="section">
    <div class="reveal mx-auto max-w-2xl text-center">
        <span class="eyebrow">{{ __('landing.pickup.eyebrow') }}</span>
        <h2 class="section-title">{{ __('landing.pickup.title') }}</h2>
        <p class="mt-4 leading-relaxed text-ink-soft">{{ __('landing.pickup.body') }}</p>
    </div>

    <div class="mt-12 grid gap-6 lg:grid-cols-2 lg:items-stretch">

        {{-- Self-pickup --}}
        <div class="reveal glass-card flex flex-col p-8">
            <div class="flex items-start justify-between gap-3">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-brand-100 to-accent-100 ring-1 ring-white/60">
                    <svg class="h-5 w-5 text-brand-700" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657 13.414 20.9a2 2 0 0 1-2.827 0l-4.244-4.243a8 8 0 1 1 11.314 0ZM15 11a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                    </svg>
                </span>
                <span class="rounded-full bg-brand-50 px-2.5 py-1 text-[11px] font-bold tracking-wide text-brand-700 uppercase ring-1 ring-brand-100">
                    {{ __('landing.pickup.self_badge') }}
                </span>
            </div>

            <h3 class="mt-5 text-lg font-semibold">{{ __('landing.pickup.self_title') }}</h3>
            <p class="mt-2 text-sm leading-relaxed text-ink-soft">{{ __('landing.pickup.self_body') }}</p>

            <div class="mt-5 rounded-xl bg-white/70 p-4 ring-1 ring-black/5">
                <p class="text-xs font-bold tracking-wide text-ink-muted uppercase">{{ __('landing.pickup.address_label') }}</p>
                <address class="mt-1.5 text-sm leading-relaxed font-semibold text-ink not-italic">{{ config('jubahrunner.address') }}</address>
                <a href="{{ config('jubahrunner.map_url') }}" target="_blank" rel="noopener noreferrer"
                   class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600 transition hover:text-brand-700">
                    {{ __('landing.pickup.directions') }}
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3"/>
                    </svg>
                </a>
            </div>

            <div class="mt-5 overflow-hidden rounded-xl ring-1 ring-black/5">
                <iframe
                    src="{{ config('jubahrunner.map_embed_url') }}"
                    title="{{ __('landing.pickup.address_label') }}: {{ config('jubahrunner.address') }}"
                    class="h-48 w-full"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>

        {{-- Kuantan cash-on-delivery --}}
        <div class="reveal glass-card flex flex-col p-8" style="transition-delay: 90ms">
            <div class="flex items-start justify-between gap-3">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-brand-100 to-accent-100 ring-1 ring-white/60">
                    <svg class="h-5 w-5 text-brand-700" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/>
                    </svg>
                </span>
                <span class="rounded-full bg-accent-100 px-2.5 py-1 text-[11px] font-bold tracking-wide text-accent-600 uppercase ring-1 ring-accent-300/50">
                    {{ __('landing.pickup.cod_badge') }}
                </span>
            </div>

            <h3 class="mt-5 text-lg font-semibold">{{ __('landing.pickup.cod_title') }}</h3>
            <p class="mt-2 text-sm leading-relaxed text-ink-soft">{{ __('landing.pickup.cod_body') }}</p>

            <p class="mt-5 flex items-start gap-2 rounded-xl bg-brand-50/70 px-4 py-3 text-sm text-brand-700 ring-1 ring-brand-100">
                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 3h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                <span>{{ __('landing.pickup.note') }}</span>
            </p>

            <a href="{{ config('jubahrunner.whatsapp_url') }}" target="_blank" rel="noopener noreferrer"
               class="btn-ghost mt-auto w-full sm:w-auto">
                {{ __('landing.pickup.cod_cta') }}
            </a>
        </div>
    </div>

    <dl class="reveal mt-6 grid gap-4 sm:grid-cols-2">
        @foreach ($schedule as $fact)
            <div class="rounded-xl bg-white/70 p-5 ring-1 ring-black/5">
                <dt class="text-xs font-bold tracking-wide text-ink-muted uppercase">{{ $fact['label'] }}</dt>
                <dd class="mt-1.5 font-semibold text-ink">{{ $fact['value'] }}</dd>
            </div>
        @endforeach
    </dl>
</section>
