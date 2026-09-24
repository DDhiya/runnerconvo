<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

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
        $middleware->redirectUsersTo(fn () => route('admin.runners.index'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
