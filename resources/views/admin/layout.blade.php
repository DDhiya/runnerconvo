<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Admin') &middot; {{ config('app.name') }}</title>

    {{-- Internal tool, not marketing copy — keep it out of search results. --}}
    <meta name="robots" content="noindex, nofollow">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-canvas font-sans text-ink antialiased">

    @php
        // Faculties, robe sizes and sessions share one route; the {type} segment tells them apart.
        $optionType = request()->route('type');
        $nav = [
            ['Bookings', route('admin.bookings.index'), request()->routeIs('admin.bookings.*')],
            ['Runners', route('admin.runners.index'), request()->routeIs('admin.runners.*')],
            ['Faculties', route('admin.options.index', 'faculty'), $optionType === 'faculty'],
            ['Robe sizes', route('admin.options.index', 'robe_size'), $optionType === 'robe_size'],
            ['Sessions', route('admin.options.index', 'convocation_session'), $optionType === 'convocation_session'],
        ];
    @endphp

    <header class="border-b border-black/5 bg-white/70 backdrop-blur-xl">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-x-6 gap-y-3 px-5 py-4 sm:px-8">
            <a href="{{ route('admin.bookings.index') }}" class="text-sm font-bold tracking-tight text-ink">
                <span class="text-brand-600">Jubah</span><span class="text-accent-400">Panda</span>
                <span class="ml-1 font-medium text-ink-muted">Admin</span>
            </a>

            @auth
                <nav aria-label="Admin" class="order-last flex w-full flex-wrap gap-x-5 gap-y-1 text-sm font-semibold sm:order-none sm:w-auto">
                    @foreach ($nav as [$label, $href, $active])
                        <a href="{{ $href }}" @if ($active) aria-current="page" @endif
                           class="transition {{ $active ? 'text-brand-700' : 'text-ink-soft hover:text-brand-700' }}">{{ $label }}</a>
                    @endforeach
                </nav>

                <div class="flex items-center gap-4 text-sm text-ink-soft">
                    <span>{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="font-semibold text-brand-600 transition hover:text-brand-700">
                            Log out
                        </button>
                    </form>
                </div>
            @endauth
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-5 py-10 sm:px-8">
        @if (session('status'))
            <div class="mb-6 rounded-xl bg-brand-50 px-4 py-3 text-sm font-medium text-brand-700 ring-1 ring-brand-100">
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </main>

</body>
</html>
