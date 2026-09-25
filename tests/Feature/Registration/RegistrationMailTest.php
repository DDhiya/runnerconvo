<?php

namespace Tests\Feature\Registration;

use App\Mail\BookingConfirmation;
use App\Mail\NewBookingAlert;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationMailTest extends TestCase
{
    use RefreshDatabase;
    use RegistrationPayload;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedOptions();
        config([
            'jubahrunner.email' => 'support@jubahpanda.my',
            'jubahrunner.notify_emails' => ['owner@example.com', 'SUPPORT@jubahpanda.my'],
        ]);
    }

    public function test_a_graduate_who_gives_an_email_gets_a_confirmation_in_their_language(): void
    {
        Mail::fake();

        $this->withSession(['locale' => 'ms'])
            ->post('/register', $this->validPayload(['email' => '  Aisyah@Example.COM ']))
            ->assertRedirect(route('register.done'));

        $booking = Booking::sole();
        $this->assertSame('aisyah@example.com', $booking->email);

        Mail::assertSent(BookingConfirmation::class, fn (BookingConfirmation $mail) => $mail->hasTo('aisyah@example.com')
            && $mail->locale === 'ms'
            && $mail->booking->is($booking));
    }

    public function test_no_email_means_no_confirmation_but_the_team_is_still_alerted(): void
    {
        Mail::fake();

        $this->post('/register', $this->validPayload());

        Mail::assertNotSent(BookingConfirmation::class);
        Mail::assertSent(NewBookingAlert::class, 1);
    }

    public function test_the_team_alert_is_one_message_to_every_team_address_deduplicated(): void
    {
        Mail::fake();

        $this->post('/register', $this->validPayload());

        Mail::assertSent(NewBookingAlert::class, function (NewBookingAlert $mail) {
            return $mail->hasTo('support@jubahpanda.my')
                && $mail->hasTo('owner@example.com')
                && count($mail->to) === 2;
        });
    }

    public function test_an_invalid_email_is_a_localised_validation_error(): void
    {
        Mail::fake();

        $this->withSession(['locale' => 'ms'])
            ->post('/register', $this->validPayload(['email' => 'not-an-email']))
            ->assertSessionHasErrors(['email' => __('register.errors.email_format', [], 'ms')]);

        $this->assertDatabaseCount('bookings', 0);
        Mail::assertNothingSent();
    }

    public function test_a_mail_failure_never_breaks_a_saved_booking(): void
    {
        Exceptions::fake();
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('Resend is down'));

        $this->post('/register', $this->validPayload(['email' => 'aisyah@example.com']))
            ->assertRedirect(route('register.done'));

        $this->assertDatabaseCount('bookings', 1);
        Exceptions::assertReported(fn (\RuntimeException $e) => $e->getMessage() === 'Resend is down');
    }

    public function test_the_done_page_mentions_the_email_only_when_one_was_given(): void
    {
        Mail::fake();

        $this->post('/register', $this->validPayload(['email' => 'aisyah@example.com']));
        $this->get('/register/done')->assertSee('We are also emailing your reference to aisyah@example.com');
    }

    public function test_the_confirmation_renders_the_reference_in_malay(): void
    {
        $booking = Booking::factory()->create(['locale' => 'ms', 'email' => 'a@example.com']);

        $mail = (new BookingConfirmation($booking))->locale('ms');

        $mail->assertHasSubject('Tempahan JubahPanda anda '.$booking->reference);
        $mail->assertSeeInHtml($booking->reference);
        $mail->assertSeeInHtml('Nombor rujukan anda');
        $mail->assertSeeInHtml('wa.me/');
    }

    public function test_typed_values_cannot_become_links_in_the_team_alert(): void
    {
        $booking = Booking::factory()->create([
            'full_name' => '[Pay here](https://evil.example)',
            'notes' => '**urgent** <script>alert(1)</script>',
        ]);

        $html = (new NewBookingAlert($booking))->render();

        $this->assertStringNotContainsString('href="https://evil.example"', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<strong>urgent</strong>', $html);
        $this->assertStringContainsString(route('admin.bookings.show', $booking), $html);
    }
}
