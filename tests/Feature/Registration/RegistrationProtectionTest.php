<?php

namespace Tests\Feature\Registration;

use App\Models\Booking;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class RegistrationProtectionTest extends TestCase
{
    use RefreshDatabase;
    use RegistrationPayload;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedOptions();
    }

    public function test_a_filled_honeypot_is_rejected_visibly_and_creates_nothing(): void
    {
        $this->post('/register', $this->validPayload(['contact_me_by_fax_only' => 'spam']))
            ->assertSessionHasErrors('form');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_a_form_submitted_too_fast_is_rejected(): void
    {
        $this->post('/register', $this->validPayload(['_started' => Crypt::encryptString((string) now()->timestamp)]))
            ->assertSessionHasErrors('form');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_a_tampered_or_missing_timer_is_rejected(): void
    {
        $this->post('/register', $this->validPayload(['_started' => 'not-a-real-token']))->assertSessionHasErrors('form');
        $this->post('/register', $this->validPayload(['_started' => null]))->assertSessionHasErrors('form');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_the_timer_passes_once_enough_time_has_elapsed(): void
    {
        $payload = $this->validPayload(['_started' => Crypt::encryptString((string) now()->timestamp)]);

        $this->travel(5)->seconds();

        $this->post('/register', $payload)->assertRedirect(route('register.done'));
    }

    public function test_the_eleventh_post_in_a_minute_is_throttled(): void
    {
        // An empty POST fails validation, but every POST still counts towards the limit.
        for ($i = 0; $i < 10; $i++) {
            $this->post('/register', [])->assertStatus(302);
        }

        $this->post('/register', [])->assertStatus(429);
    }

    public function test_visitors_behind_the_same_cloudflare_edge_get_separate_buckets(): void
    {
        // Regression test for trustProxies(): without it $request->ip() is the Cloudflare
        // address for everyone, and one busy visitor throttles every other graduate.
        $post = fn (string $visitor) => $this
            ->withServerVariables(['REMOTE_ADDR' => '172.64.1.1'])
            ->withHeader('X-Forwarded-For', $visitor)
            ->post('/register', []);

        for ($i = 0; $i < 10; $i++) {
            $post('203.0.113.5')->assertStatus(302);
        }

        $post('203.0.113.5')->assertStatus(429);
        $post('203.0.113.99')->assertStatus(302);
    }

    public function test_an_expired_token_returns_to_the_form_with_the_input_and_a_message(): void
    {
        // Laravel skips CSRF checks under PHPUnit; bind a copy that enforces them.
        $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app->make(Encrypter::class)) extends PreventRequestForgery
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });

        $this->from('/register')
            ->post('/register', $this->validPayload(['full_name' => 'Kept Name']))
            ->assertRedirect('/register')
            ->assertSessionHasErrors('form')
            ->assertSessionHasInput('full_name', 'Kept Name');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_the_landing_page_still_degrades_but_the_form_does_not_share_that(): void
    {
        $this->assertSame(0, Booking::count());
        $this->get('/')->assertOk();
    }
}
