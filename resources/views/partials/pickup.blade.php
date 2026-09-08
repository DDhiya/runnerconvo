@php
    $facts = [
        ['label' => __('landing.pickup.area_label'), 'value' => __('landing.pickup.area')],
        ['label' => __('landing.pickup.hours_label'), 'value' => __('landing.pickup.hours')],
        ['label' => __('landing.pickup.window_label'), 'value' => __('landing.pickup.window')],
    ];
@endphp

<section id="pickup" class="section">
    <div class="reveal glass-card grid gap-10 p-8 sm:p-12 lg:grid-cols-2 lg:items-center">

        <div>
            <span class="eyebrow">{{ __('landing.pickup.eyebrow') }}</span>
            <h2 class="section-title">{{ __('landing.pickup.title') }}</h2>
            <p class="mt-4 leading-relaxed text-ink-soft">{{ __('landing.pickup.body') }}</p>

            <p class="mt-6 flex items-start gap-2 rounded-xl bg-brand-50/70 px-4 py-3 text-sm text-brand-700 ring-1 ring-brand-100">
                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2Zm10-10V7a4 4 0 0 0-8 0v4h8Z"/>
                </svg>
                <span>{{ __('landing.pickup.note') }}</span>
            </p>
        </div>

        <dl class="grid gap-4 sm:grid-cols-3 lg:grid-cols-1">
            {{-- TODO: replace the area, hours and window values in lang/{en,ms}/landing.php --}}
            @foreach ($facts as $fact)
                <div class="rounded-xl bg-white/70 p-5 ring-1 ring-black/5">
                    <dt class="text-xs font-bold tracking-wide text-ink-muted uppercase">{{ $fact['label'] }}</dt>
                    <dd class="mt-1.5 font-semibold text-ink">{{ $fact['value'] }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
</section>
