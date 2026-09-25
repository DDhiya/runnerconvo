<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // One place resolves the Register target. The .env override wins (kill switch);
        // otherwise it is the in-app form. A composer rather than config, because route()
        // does not exist yet when config is built.
        View::composer('partials.*', fn ($view) => $view->with(
            'registerUrl', config('jubahrunner.register_url') ?: route('register'),
        ));

        // Generous on purpose: every POST counts, including ones that fail validation, and a
        // hostel, campus Wi-Fi or a mobile-carrier NAT puts many real graduates behind one IP.
        // Bots are stopped by the honeypot and timer; this only has to stop floods. The keys
        // are distinct because two limits sharing one by() would share one counter.
        RateLimiter::for('registrations', fn (Request $request) => [
            Limit::perMinute(10)->by('reg-min:'.$request->ip()),
            Limit::perHour(60)->by('reg-hour:'.$request->ip()),
        ]);
    }
}
