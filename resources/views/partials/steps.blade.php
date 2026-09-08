<section id="how" class="section">
    <div class="reveal max-w-2xl">
        <span class="eyebrow">{{ __('landing.steps.eyebrow') }}</span>
        <h2 class="section-title">{{ __('landing.steps.title') }}</h2>
        <p class="mt-4 text-lg text-ink-soft">{{ __('landing.steps.subtitle') }}</p>
    </div>

    <ol class="mt-14 grid gap-5 md:grid-cols-2">
        @foreach (__('landing.steps.items') as $i => $step)
            @php $isYou = ($step['actor'] ?? 'you') === 'you'; @endphp

            <li class="reveal glass-card relative flex gap-5 p-7" style="transition-delay: {{ $i * 70 }}ms">

                {{-- Step number --}}
                <span aria-hidden="true"
                      class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-gradient-to-br from-brand-600 to-accent-500 text-sm font-bold text-white shadow-md shadow-brand-500/25">
                    {{ $i + 1 }}
                </span>

                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                        <h3 class="text-lg font-semibold">{{ $step['title'] }}</h3>
                        <span class="rounded-full px-2 py-0.5 text-[11px] font-bold tracking-wide uppercase
                                     {{ $isYou
                                         ? 'bg-brand-50 text-brand-700 ring-1 ring-brand-100'
                                         : 'bg-accent-100 text-accent-600 ring-1 ring-accent-300/50' }}">
                            {{ $isYou ? __('landing.steps.you') : __('landing.steps.us') }}
                        </span>
                    </div>

                    <p class="mt-2 text-sm leading-relaxed text-ink-soft">{{ $step['body'] }}</p>

                    {{--
                        TODO(payment): the payment step is intentionally a placeholder.
                        When you pick a provider, replace this block with the real flow:
                          - ToyyibPay / Billplz  -> FPX online banking, lowest fees in MY
                          - DuitNow QR           -> show a QR + receipt upload
                          - Stripe               -> cards, higher fees, no FPX
                        Copy for this note lives in lang/{en,ms}/landing.php under
                        steps.items.1.body, so it can be reworded without touching Blade.
                    --}}
                    @if ($i === 1)
                        <p class="mt-3 inline-flex items-center gap-2 rounded-lg bg-brand-50/80 px-3 py-2 text-xs font-medium text-brand-700 ring-1 ring-brand-100">
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 3h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                            {{ __('landing.pricing.footnote') }}
                        </p>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
</section>
