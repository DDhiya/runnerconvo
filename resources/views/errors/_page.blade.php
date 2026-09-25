{{--
    Standalone on purpose: no @vite, no layout, no session. An error page that depends on the
    asset manifest or the DB can itself fail. Both languages are shown because the locale
    middleware may not have run when this renders.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} - JubahPanda</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 1.25rem;
               background: #fef8fa; color: #6b4c5a; font: 16px/1.6 system-ui, -apple-system, 'Segoe UI', sans-serif; }
        main { max-width: 30rem; background: #fff; border-radius: 1rem; padding: 2rem;
               box-shadow: 0 8px 30px rgb(30 27 49 / 0.08); }
        h1 { margin: 0 0 .5rem; color: #271420; font-size: 1.25rem; }
        h2 { margin: 1.5rem 0 .5rem; color: #271420; font-size: 1.05rem; }
        p { margin: 0 0 .75rem; }
        a { color: #fff; background: #c2255c; display: inline-block; padding: .65rem 1.25rem;
            border-radius: 999px; text-decoration: none; font-weight: 600; margin-top: .5rem; }
    </style>
</head>
<body>
    <main>
        @foreach (['en', 'ms'] as $locale)
            @if ($loop->first)
                <h1>{{ __('register.error_pages.'.$key.'_title', [], $locale) }}</h1>
            @else
                <h2>{{ __('register.error_pages.'.$key.'_title', [], $locale) }}</h2>
            @endif
            <p>{{ __('register.error_pages.'.$key.'_body', [], $locale) }}</p>
        @endforeach
        <a href="{{ config('jubahrunner.whatsapp_url') }}">WhatsApp</a>
    </main>
</body>
</html>
