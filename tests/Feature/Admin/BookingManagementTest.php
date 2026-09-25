<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Runner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): static
    {
        return $this->actingAs(User::factory()->create());
    }

    /**
     * The form as the show page would submit it, unchanged, plus overrides.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(Booking $booking, array $overrides = []): array
    {
        return [
            'full_name' => $booking->full_name,
            'matric_no' => $booking->matric_no,
            'phone' => $booking->phone,
            'programme_level' => $booking->programme_level,
            'faculty_id' => $booking->faculty_id,
            'robe_size_id' => $booking->robe_size_id,
            'convocation_session_id' => $booking->convocation_session_id,
            'delivery_method' => $booking->delivery_method,
            'delivery_address' => $booking->delivery_address,
            'notes' => $booking->notes,
            'status' => $booking->status->value,
            'runner_id' => $booking->runner_id,
            'admin_notes' => $booking->admin_notes,
            'amount' => number_format($booking->amount_sen / 100, 2, '.', ''),
            'paid' => $booking->isPaid() ? '1' : '0',
            'payment_method' => $booking->payment_method,
            'payment_reference' => $booking->payment_reference,
            ...$overrides,
        ];
    }

    public function test_guests_are_redirected_from_every_booking_route(): void
    {
        $booking = Booking::factory()->create();

        $this->get(route('admin.bookings.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.bookings.export'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.bookings.show', $booking))->assertRedirect(route('admin.login'));
        $this->patch(route('admin.bookings.update', $booking), [])->assertRedirect(route('admin.login'));
        $this->delete(route('admin.bookings.destroy', $booking))->assertRedirect(route('admin.login'));
    }

    public function test_the_admin_root_redirects_to_bookings(): void
    {
        $this->admin()->get('/admin')->assertRedirect('/admin/bookings');
    }

    public function test_admin_responses_are_not_cacheable(): void
    {
        $this->admin()->get(route('admin.bookings.index'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_the_list_filters_by_status_session_runner_and_payment(): void
    {
        $runner = Runner::factory()->create();
        $submitted = Booking::factory()->create(['full_name' => 'Alpha Submitted']);
        $confirmed = Booking::factory()->status(BookingStatus::Confirmed)->paid()->create(['full_name' => 'Bravo Confirmed', 'runner_id' => $runner->id]);

        $this->admin();
        $this->get(route('admin.bookings.index', ['status' => 'confirmed']))->assertSee('Bravo Confirmed')->assertDontSee('Alpha Submitted');
        $this->get(route('admin.bookings.index', ['runner_id' => $runner->id]))->assertSee('Bravo Confirmed')->assertDontSee('Alpha Submitted');
        $this->get(route('admin.bookings.index', ['runner_id' => 'none']))->assertSee('Alpha Submitted')->assertDontSee('Bravo Confirmed');
        $this->get(route('admin.bookings.index', ['paid' => 'yes']))->assertSee('Bravo Confirmed')->assertDontSee('Alpha Submitted');
        $this->get(route('admin.bookings.index', ['convocation_session_id' => $submitted->convocation_session_id]))
            ->assertSee('Alpha Submitted')->assertDontSee('Bravo Confirmed');
    }

    public function test_search_finds_by_reference_matric_and_a_phone_typed_with_a_leading_zero(): void
    {
        $target = Booking::factory()->create(['full_name' => 'Target Person', 'matric_no' => 'CB22123', 'phone' => '60123456789']);
        Booking::factory()->create(['full_name' => 'Someone Else', 'phone' => '60199999999']);

        $this->admin();
        foreach ([$target->reference, 'cb 22-123', '012-345'] as $term) {
            $this->get(route('admin.bookings.index', ['q' => $term]))->assertSee('Target Person')->assertDontSee('Someone Else');
        }
    }

    public function test_email_is_searchable_and_editable(): void
    {
        $booking = Booking::factory()->create(['full_name' => 'Has Email', 'email' => 'someone@example.com']);
        Booking::factory()->create(['full_name' => 'No Email']);

        $this->admin()->get(route('admin.bookings.index', ['q' => 'someone@']))->assertSee('Has Email')->assertDontSee('No Email');

        $this->patch(route('admin.bookings.update', $booking), $this->payload($booking, ['email' => 'New@Example.com']))
            ->assertSessionDoesntHaveErrors();
        $this->assertSame('new@example.com', $booking->fresh()->email);

        $this->patch(route('admin.bookings.update', $booking), $this->payload($booking, ['email' => '']));
        $this->assertNull($booking->fresh()->email);
    }

    public function test_the_show_page_offers_a_whatsapp_link_in_the_registered_language(): void
    {
        $booking = Booking::factory()->create(['phone' => '60123456789', 'locale' => 'ms']);

        $this->admin()->get(route('admin.bookings.show', $booking))
            ->assertOk()
            ->assertSee('https://wa.me/60123456789?text=', false)
            ->assertSee(rawurlencode('ini JubahPanda mengenai tempahan jubah anda'), false);
    }

    public function test_details_can_be_edited_and_are_normalised(): void
    {
        $booking = Booking::factory()->create();

        $this->admin()->patch(route('admin.bookings.update', $booking), $this->payload($booking, [
            'full_name' => 'Corrected Name', 'matric_no' => 'cb 22-777', 'phone' => '019-921 5166', 'amount' => '50.00',
        ]))->assertRedirect(route('admin.bookings.show', $booking));

        $booking->refresh();
        $this->assertSame('Corrected Name', $booking->full_name);
        $this->assertSame('CB22777', $booking->matric_no);
        $this->assertSame('60199215166', $booking->phone);
        $this->assertSame(5000, $booking->amount_sen);
    }

    public function test_a_runner_can_be_assigned_and_an_inactive_one_stays_selectable(): void
    {
        $inactive = Runner::factory()->inactive()->create(['name' => 'Retired Runner']);
        $booking = Booking::factory()->create(['runner_id' => $inactive->id]);

        $this->admin()->get(route('admin.bookings.show', $booking))->assertSee('Retired Runner (inactive)');

        $active = Runner::factory()->create();
        $this->patch(route('admin.bookings.update', $booking), $this->payload($booking, ['runner_id' => $active->id]));

        $this->assertSame($active->id, $booking->fresh()->runner_id);
    }

    public function test_a_deactivated_session_does_not_block_saving_an_unrelated_edit(): void
    {
        $booking = Booking::factory()->create();
        $booking->convocationSession->update(['is_active' => false]);

        $this->admin()->patch(route('admin.bookings.update', $booking), $this->payload($booking, ['admin_notes' => 'Called, no answer']))
            ->assertSessionDoesntHaveErrors();

        $this->assertSame('Called, no answer', $booking->fresh()->admin_notes);
    }

    public function test_changing_status_stamps_the_stage_once(): void
    {
        $booking = Booking::factory()->create();

        $this->admin()->patch(route('admin.bookings.update', $booking), $this->payload($booking, ['status' => 'confirmed']));

        $booking->refresh();
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertNotNull($booking->confirmed_at);
    }

    public function test_a_self_pickup_booking_cannot_be_collected_unpaid(): void
    {
        $booking = Booking::factory()->create();

        $this->admin()->patch(route('admin.bookings.update', $booking), $this->payload($booking, ['status' => 'collected']))
            ->assertSessionHasErrors('status');

        $this->patch(route('admin.bookings.update', $booking), $this->payload($booking, ['status' => 'collected', 'paid' => '1']))
            ->assertSessionDoesntHaveErrors();

        $this->assertSame(BookingStatus::Collected, $booking->fresh()->status);
    }

    public function test_handing_over_requires_paid_but_cod_can_be_paid_in_the_same_save(): void
    {
        $booking = Booking::factory()->cod()->status(BookingStatus::Collected)->create();

        $this->admin()->patch(route('admin.bookings.update', $booking), $this->payload($booking, ['status' => 'handed_over']))
            ->assertSessionHasErrors('status');

        $this->patch(route('admin.bookings.update', $booking), $this->payload($booking, [
            'status' => 'handed_over', 'paid' => '1', 'payment_method' => 'cash',
        ]))->assertSessionDoesntHaveErrors();

        $booking->refresh();
        $this->assertSame(BookingStatus::HandedOver, $booking->status);
        $this->assertNotNull($booking->paid_at);
        $this->assertSame('cash', $booking->payment_method);
    }

    public function test_unticking_paid_clears_the_payment_details(): void
    {
        $booking = Booking::factory()->paid()->create(['payment_reference' => 'RCPT-1']);

        $this->admin()->patch(route('admin.bookings.update', $booking), $this->payload($booking, ['paid' => '0']));

        $booking->refresh();
        $this->assertNull($booking->paid_at);
        $this->assertNull($booking->payment_method);
        $this->assertNull($booking->payment_reference);
    }

    public function test_uncancelling_onto_a_matric_another_live_booking_holds_fails(): void
    {
        $cancelled = Booking::factory()->cancelled()->create(['matric_no' => 'CB22001']);
        Booking::factory()->create(['matric_no' => 'CB22001']);

        $this->admin()->patch(route('admin.bookings.update', $cancelled), $this->payload($cancelled, ['status' => 'submitted']))
            ->assertSessionHasErrors('matric_no');

        $this->assertSame(BookingStatus::Cancelled, $cancelled->fresh()->status);
    }

    public function test_a_booking_can_be_deleted_permanently(): void
    {
        $booking = Booking::factory()->create();

        $this->admin()->delete(route('admin.bookings.destroy', $booking))->assertRedirect(route('admin.bookings.index'));

        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
    }
}
