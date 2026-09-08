<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
})->name('home');

/*
 * Language switch. The locale is whitelisted in SetLocale::SUPPORTED, so an
 * unknown segment simply falls through to the default without changing state.
 */
Route::get('/lang/{locale}', function (Request $request, string $locale) {
    if (in_array($locale, SetLocale::SUPPORTED, true)) {
        $request->session()->put('locale', $locale);
    }

    return back();
})->name('locale.switch');
