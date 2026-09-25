<?php

namespace Tests\Feature\Registration;

use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RegistrationFormTest extends TestCase
{
    use RefreshDatabase;
    use RegistrationPayload;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedOptions();
    }

    public function test_the_form_renders_in_both_languages(): void
    {
        $this->get('/register')->assertOk()->assertSee('Book your robe collection')->assertSee('Computing');

        $this->withSession(['locale' => 'ms'])->get('/register')
            ->assertOk()->assertSee('Tempah pengambilan jubah anda')->assertSee('Pengkomputeran');
    }

    public function test_a_valid_submission_creates_a_normalised_booking_and_shows_the_reference(): void
    {
        $response = $this->post('/register', $this->validPayload(['matric_no' => 'cb 22-001']));

        $response->assertRedirect(route('register.done'));

        $booking = Booking::sole();
        $this->assertSame('60123456789', $booking->phone);
        $this->assertSame('CB22001', $booking->matric_no);
        $this->assertSame('submitted', $booking->status->value);
        $this->assertSame(4500, $booking->amount_sen);
        $this->assertSame('en', $booking->locale);
        $this->assertSame(config('jubahrunner.privacy_version'), $booking->privacy_version);
        $this->assertNotNull($booking->consented_at);
        $this->assertMatchesRegularExpression('/^JP-[A-HJKMNP-Z2-9]{6}$/', $booking->reference);

        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee($booking->reference)
            ->assertSee('https://wa.me/'.config('jubahrunner.whatsapp_number').'?text=', false)
            ->assertSee(rawurlencode($booking->reference), false);
    }

    public function test_the_done_page_without_a_session_goes_back_to_the_form(): void
    {
        $this->get('/register/done')->assertRedirect(route('register'));
    }

    public function test_the_registered_language_is_stored(): void
    {
        $this->withSession(['locale' => 'ms'])->post('/register', $this->validPayload());

        $this->assertSame('ms', Booking::sole()->locale);
    }

    public function test_required_fields_are_enforced(): void
    {
        $this->post('/register', $this->validPayload([
            'full_name' => '', 'matric_no' => '', 'phone' => '', 'programme_level' => '',
            'faculty_id' => '', 'robe_size_id' => '', 'convocation_session_id' => '',
            'delivery_method' => '', 'documents_ack' => null, 'consent' => null,
        ]))->assertSessionHasErrors([
            'full_name', 'matric_no', 'phone', 'programme_level', 'faculty_id', 'robe_size_id',
            'convocation_session_id', 'delivery_method', 'documents_ack', 'consent',
        ]);

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_cod_requires_an_address_but_pickup_does_not(): void
    {
        $this->post('/register', $this->validPayload(['delivery_method' => 'cod']))
            ->assertSessionHasErrors('delivery_address');

        $this->post('/register', $this->validPayload(['delivery_method' => 'cod', 'delivery_address' => 'No. 1, Jalan Test, Kuantan']))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_inactive_and_wrong_list_options_are_rejected(): void
    {
        $this->options['faculty']->update(['is_active' => false]);

        $this->post('/register', $this->validPayload())->assertSessionHasErrors('faculty_id');

        $this->options['faculty']->update(['is_active' => true]);
        $this->post('/register', $this->validPayload(['faculty_id' => $this->options['session']->id]))
            ->assertSessionHasErrors('faculty_id');
    }

    public function test_matric_format_depends_on_programme_level(): void
    {
        foreach (['CB22000', 'CB2201', 'CBA22001'] as $bad) {
            $this->post('/register', $this->validPayload(['matric_no' => $bad]))->assertSessionHasErrors('matric_no');
        }

        $this->post('/register', $this->validPayload(['programme_level' => 'phd', 'matric_no' => 'PHD21001']))
            ->assertSessionDoesntHaveErrors();
    }

    public function test_a_duplicate_matric_in_another_format_is_a_validation_error_not_a_500(): void
    {
        Booking::factory()->create(['matric_no' => 'CB22001']);

        $this->post('/register', $this->validPayload(['matric_no' => 'cb-22 001']))
            ->assertSessionHasErrors('matric_no');

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_a_cancelled_booking_does_not_block_reregistering(): void
    {
        Booking::factory()->cancelled()->create(['matric_no' => 'CB22001']);

        $this->post('/register', $this->validPayload())->assertRedirect(route('register.done'));

        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_a_closed_window_shows_the_closed_card_and_creates_nothing(): void
    {
        config(['jubahrunner.registration_closes_at' => now()->subDay()->format('Y-m-d H:i')]);

        $this->get('/register')->assertOk()->assertSee('Registration has closed')->assertDontSee('name="matric_no"', false);
        $this->post('/register', $this->validPayload())->assertRedirect(route('register'));

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_an_empty_option_list_shows_opens_soon(): void
    {
        $this->options['robe']->update(['is_active' => false]);

        $this->get('/register')->assertOk()->assertSee('Registration opens soon');
        $this->post('/register', $this->validPayload())->assertRedirect(route('register'));

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_the_write_path_is_not_rescued(): void
    {
        Schema::drop('bookings');

        // Unlike the landing page's rescue(), a failed booking must NOT look like success.
        $this->post('/register', $this->validPayload(['matric_no' => 'CB22009']))->assertServerError();
    }
}
