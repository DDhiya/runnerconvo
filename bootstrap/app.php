<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // `commands:` above is a command ROUTE file, not the command directory scan —
    // without this, classes under app/Console/Commands/ are never registered.
    ->withCommands()
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
        ]);

        // Every request reaches php-fpm from a Cloudflare edge IP, so without this
        // $request->ip() is Cloudflare's, and per-IP throttles lump strangers together.
        // Trust ONLY Cloudflare's published ranges (https://www.cloudflare.com/ips/,
        // copied 2026-09-25) so $request->ip() is the visitor. A spoofed X-Forwarded-For
        // from a client is harmless: Cloudflare appends the real IP and Symfony reads
        // from the right, skipping trusted hops. Refresh this list if Cloudflare adds
        // ranges (rare).
        $middleware->trustProxies(
            at: [
                '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
                '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
                '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
                '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
                '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32',
                '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
            ],
            headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO,
        );

        // Laravel's own default here is redirectGuestsTo(route('login')), which
        // doesn't exist in this app. Setting it here (rather than in a service
        // provider's boot()) matters: this closure runs inside the same
        // afterResolving(HttpKernel) callback as that default, in a fixed order,
        // in both a real request AND the test environment. A provider's boot()
        // does not have that guarantee — it runs relative to *provider*
        // bootstrapping, whose order relative to HTTP-kernel resolution differs
        // between a real request (kernel resolved first) and a test (providers
        // booted first via the console kernel, then the kernel is resolved lazily
        // on the first HTTP call) — so a provider-based override is silently
        // clobbered by this default in tests only.
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.bookings.index'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // A registration form left open past SESSION_LIFETIME would otherwise lose
        // everything the graduate typed behind a bare English "419 Page Expired". The
        // handler runs prepareException() BEFORE render callbacks, which turns a
        // TokenMismatchException into HttpException(419) — so this must match
        // HttpException, not TokenMismatchException, or it never fires.
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() === 419 && $request->routeIs('register.store')) {
                return back()
                    ->withInput($request->except('_token', '_started'))
                    ->withErrors(['form' => __('register.errors.expired')]);
            }
        });
    })->create();
