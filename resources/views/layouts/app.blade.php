<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', __('landing.meta.title'))</title>
    <meta name="description" content="{{ __('landing.meta.description') }}">

    {{-- These links get pasted into WhatsApp and Instagram bios, so the preview card matters. --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ __('landing.meta.title') }}">
    <meta property="og:description" content="{{ __('landing.meta.description') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="{{ app()->getLocale() === 'ms' ? 'ms_MY' : 'en_US' }}">
    <meta property="og:image" content="{{ asset('images/og-image.png') }}">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="canonical" href="{{ url()->current() }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('images/logo-mark-64.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <meta name="theme-color" content="#c2255c">

    {{-- Set before paint so the reveal animation never leaves content blank when JS is off. --}}
    <script>document.documentElement.classList.add('js');</script>

    @stack('head')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="relative min-h-screen font-sans antialiased">

    {{-- Gradient ground: a fixed wash plus three blurred colour blobs, echoing
         the mascot's blush-pink badge and magenta cape. --}}
    <div aria-hidden="true" class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute inset-0 bg-[linear-gradient(160deg,#fdf1f5_0%,#fef8fa_45%,#fce7f0_100%)]"></div>
        <div class="absolute -top-40 -left-32 h-[32rem] w-[32rem] rounded-full bg-brand-200/50 blur-3xl"></div>
        <div class="absolute top-1/3 -right-40 h-[34rem] w-[34rem] rounded-full bg-accent-300/40 blur-3xl"></div>
        <div class="absolute -bottom-48 left-1/4 h-[30rem] w-[30rem] rounded-full bg-brand-100/60 blur-3xl"></div>
    </div>

    <a href="#main"
       class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:rounded-full focus:bg-white focus:px-5 focus:py-3 focus:text-sm focus:font-semibold focus:text-ink focus:shadow-lg">
        {{ __('landing.nav.skip') }}
    </a>

    @include('partials.nav')

    <main id="main">
        @yield('content')
    </main>

    @include('partials.footer')

</body>
</html>
