<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Contracts\View\View;

class PrivacyController extends Controller
{
    /**
     * Both languages on one page, current locale first. PDPA s.7(3) requires the notice in
     * the national language AND English; rendering both satisfies that literally rather
     * than relying on the language toggle.
     */
    public function __invoke(): View
    {
        $current = app()->getLocale();
        $locales = collect(SetLocale::SUPPORTED)
            ->sortBy(fn (string $locale) => $locale === $current ? 0 : 1)
            ->values();

        return view('privacy', ['locales' => $locales]);
    }
}
