{{--
    Team directory. Data comes from the `runners` table via LandingController and is
    managed at /admin — unlike every other partial, this one is not config-driven.
--}}
@if ($runners->isNotEmpty())
<section id="runners" class="section">
    <div class="reveal mx-auto max-w-2xl text-center">
        <span class="eyebrow">{{ __('landing.runners.eyebrow') }}</span>
        <h2 class="section-title">{{ __('landing.runners.title') }}</h2>
        <p class="mt-4 text-lg text-ink-soft">{{ __('landing.runners.subtitle') }}</p>
    </div>

    <ul class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($runners as $i => $runner)
            <li class="reveal glass-card flex flex-col items-center p-6 text-center"
                style="transition-delay: {{ $i * 60 }}ms">

                <span aria-hidden="true"
                      class="grid h-14 w-14 place-items-center rounded-full bg-gradient-to-br from-brand-600 to-accent-500 text-lg font-bold text-white shadow-md shadow-brand-500/25">
                    {{ str($runner->name)->substr(0, 1)->upper() }}
                </span>

                <h3 class="mt-4 text-base font-semibold">{{ $runner->name }}</h3>

                {{-- aria-label, not the visible text: eight links all reading "Chat on
                     WhatsApp" are indistinguishable in a screen reader's link list. --}}
                <a href="{{ $runner->whatsapp_url }}" target="_blank" rel="noopener noreferrer"
                   aria-label="{{ __('landing.runners.chat_with', ['name' => $runner->name]) }}"
                   class="btn-ghost mt-4 w-full !px-4 !py-2.5 !text-xs">
                    <x-whatsapp-icon class="h-4 w-4 text-brand-600" />
                    {{ __('landing.runners.chat') }}
                </a>
            </li>
        @endforeach
    </ul>
</section>
@endif
