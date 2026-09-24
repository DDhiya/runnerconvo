<?php

namespace App\Http\Controllers;

use App\Models\Runner;
use Illuminate\Contracts\View\View;

class LandingController extends Controller
{
    public function __invoke(): View
    {
        // rescue(): a missing or corrupt SQLite file would otherwise 500 the ENTIRE
        // marketing page. Degrading to "no runners section" keeps every config-driven
        // CTA working, which is the property the page has today.
        $runners = rescue(fn () => Runner::query()->active()->ordered()->get(), collect(), report: true);

        return view('landing', ['runners' => $runners]);
    }
}
