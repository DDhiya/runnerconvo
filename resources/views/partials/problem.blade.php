@php
    $icons = [
        // Queue / people
        'M17 20h5v-2a3 3 0 0 0-5.36-1.86M17 20H7m10 0v-2c0-.66-.13-1.3-.36-1.86m0 0a5 5 0 0 0-9.28 0M7 20H2v-2a3 3 0 0 1 5.36-1.86M7 20v-2c0-.66.13-1.3.36-1.86m0 0a5 5 0 0 1 9.28 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
        // Map pin
        'M17.657 16.657 13.414 20.9a2 2 0 0 1-2.827 0l-4.244-4.243a8 8 0 1 1 11.314 0ZM15 11a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
        // Clock
        'M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
    ];
@endphp

<section id="problem" class="section">
    <div class="reveal max-w-2xl">
        <span class="eyebrow">{{ __('landing.problem.eyebrow') }}</span>
        <h2 class="section-title">{{ __('landing.problem.title') }}</h2>
    </div>

    <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach (__('landing.problem.items') as $i => $item)
            <div class="reveal glass-card p-7" style="transition-delay: {{ $i * 90 }}ms">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-gradient-to-br from-brand-100 to-accent-100 ring-1 ring-white/60">
                    <svg class="h-5 w-5 text-brand-700" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$i] ?? $icons[0] }}"/>
                    </svg>
                </span>
                <h3 class="mt-5 text-lg font-semibold">{{ $item['title'] }}</h3>
                <p class="mt-2 text-sm leading-relaxed text-ink-soft">{{ $item['body'] }}</p>
            </div>
        @endforeach
    </div>
</section>
