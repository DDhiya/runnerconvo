<?php

use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\BookingExportController;
use App\Http\Controllers\Admin\BookingOptionController;
use App\Http\Controllers\Admin\BookingOptionOrderController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\RunnerController;
use App\Http\Controllers\Admin\RunnerOrderController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PrivacyController;
use App\Http\Controllers\RegistrationController;
use App\Http\Middleware\AdminLocale;
use App\Http\Middleware\NoStore;
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

Route::get('register', [RegistrationController::class, 'create'])->name('register');
Route::post('register', [RegistrationController::class, 'store'])
    ->middleware('throttle:registrations')->name('register.store');
Route::get('register/done', [RegistrationController::class, 'show'])->name('register.done');
Route::get('privacy', PrivacyController::class)->name('privacy');

Route::prefix('admin')->middleware(AdminLocale::class)->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'create'])->name('login');
        Route::post('login', [LoginController::class, 'store'])
            ->middleware('throttle:5,1')->name('login.store');
    });

    Route::middleware(['auth', NoStore::class])->group(function () {
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

        // Bookings are the daily work; /admin lands there. (A plain redirect, not a closure,
        // so it survives route:cache.)
        Route::redirect('/', '/admin/bookings')->name('home');

        // `export` must be declared before {booking}, or "export" is read as a reference.
        Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('bookings/export', BookingExportController::class)->name('bookings.export');
        Route::get('bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
        Route::patch('bookings/{booking}', [BookingController::class, 'update'])->name('bookings.update');
        Route::delete('bookings/{booking}', [BookingController::class, 'destroy'])->name('bookings.destroy');

        Route::get('runners', [RunnerController::class, 'index'])->name('runners.index');
        Route::get('runners/create', [RunnerController::class, 'create'])->name('runners.create');
        Route::post('runners', [RunnerController::class, 'store'])->name('runners.store');
        Route::get('runners/{runner}/edit', [RunnerController::class, 'edit'])->name('runners.edit');
        Route::patch('runners/{runner}', [RunnerController::class, 'update'])->name('runners.update');
        Route::delete('runners/{runner}', [RunnerController::class, 'destroy'])->name('runners.destroy');
        Route::patch('runners/{runner}/move/{direction}', RunnerOrderController::class)
            ->whereIn('direction', ['up', 'down'])->name('runners.move');

        // Faculties, robe sizes and convocation sessions share one controller and one set of views.
        Route::prefix('options/{type}')->whereIn('type', ['faculty', 'robe_size', 'convocation_session'])
            ->name('options.')->group(function () {
                Route::get('/', [BookingOptionController::class, 'index'])->name('index');
                Route::get('create', [BookingOptionController::class, 'create'])->name('create');
                Route::post('/', [BookingOptionController::class, 'store'])->name('store');
                Route::get('{option}/edit', [BookingOptionController::class, 'edit'])->name('edit');
                Route::patch('{option}', [BookingOptionController::class, 'update'])->name('update');
                Route::delete('{option}', [BookingOptionController::class, 'destroy'])->name('destroy');
                Route::patch('{option}/move/{direction}', BookingOptionOrderController::class)
                    ->whereIn('direction', ['up', 'down'])->name('move');
            });
    });
});
