<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * The admin is hardcoded English. Without this, an admin whose session holds "ms" (from
 * the landing-page toggle) would get Malay validation errors inside the English admin,
 * now that lang/ms/validation.php exists.
 */
class AdminLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale('en');

        return $next($request);
    }
}
