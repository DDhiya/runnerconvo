<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    public function test_the_server_error_page_says_the_booking_was_not_saved_in_both_languages(): void
    {
        Route::get('/_boom', fn () => throw new \RuntimeException('boom'));
        // Debug pages would replace our view; this is what production renders.
        config(['app.debug' => false]);

        $this->get('/_boom')
            ->assertStatus(500)
            ->assertSee('your booking was NOT saved')
            ->assertSee('tempahan anda TIDAK disimpan')
            ->assertSee(config('jubahrunner.whatsapp_url'), false);
    }

    public function test_the_throttle_page_is_friendly(): void
    {
        $this->get('/_nothing');   // warm up routing

        Route::get('/_slow', fn () => abort(429));

        $this->get('/_slow')->assertStatus(429)->assertSee('Too many attempts')->assertSee('Terlalu banyak percubaan');
    }
}
