<?php

use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\RunnerController;
use App\Http\Controllers\Admin\RunnerOrderController;
use App\Http\Controllers\LandingController;
use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');

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

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'create'])->name('login');
        Route::post('login', [LoginController::class, 'store'])
            ->middleware('throttle:5,1')->name('login.store');
    });

    Route::middleware('auth')->group(function () {
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

        Route::get('/', [RunnerController::class, 'index'])->name('runners.index');
        Route::get('runners/create', [RunnerController::class, 'create'])->name('runners.create');
        Route::post('runners', [RunnerController::class, 'store'])->name('runners.store');
        Route::get('runners/{runner}/edit', [RunnerController::class, 'edit'])->name('runners.edit');
        Route::patch('runners/{runner}', [RunnerController::class, 'update'])->name('runners.update');
        Route::delete('runners/{runner}', [RunnerController::class, 'destroy'])->name('runners.destroy');
        Route::patch('runners/{runner}/move/{direction}', RunnerOrderController::class)
            ->whereIn('direction', ['up', 'down'])->name('runners.move');
    });
});
