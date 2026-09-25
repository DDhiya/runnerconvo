<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingOptionType;
use App\Models\Booking;
use App\Models\BookingOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingOptionManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): static
    {
        return $this->actingAs(User::factory()->create());
    }

    public function test_guests_are_redirected(): void
    {
        $this->get(route('admin.options.index', 'faculty'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.options.store', 'faculty'), [])->assertRedirect(route('admin.login'));
    }

    public function test_an_unknown_list_is_a_404(): void
    {
        $this->admin()->get('/admin/options/nonsense')->assertNotFound();
    }

    public function test_an_entry_is_appended_to_the_end_of_its_own_list(): void
    {
        BookingOption::factory()->faculty()->create(['position' => 5]);
        BookingOption::factory()->robeSize()->create(['position' => 50]);

        $this->admin()->post(route('admin.options.store', 'faculty'), [
            'label_en' => 'Engineering', 'label_ms' => 'Kejuruteraan', 'is_active' => '1',
        ])->assertRedirect(route('admin.options.index', 'faculty'));

        $created = BookingOption::query()->where('label_en', 'Engineering')->sole();
        $this->assertSame(BookingOptionType::Faculty, $created->type);
        $this->assertSame(6, $created->position, 'positions are per list, so the robe-size 50 is ignored');
    }

    public function test_both_labels_are_required(): void
    {
        $this->admin()->post(route('admin.options.store', 'faculty'), ['label_en' => 'Only English', 'is_active' => '1'])
            ->assertSessionHasErrors('label_ms');

        $this->post(route('admin.options.store', 'faculty'), ['label_ms' => 'Hanya Melayu', 'is_active' => '1'])
            ->assertSessionHasErrors('label_en');

        $this->assertDatabaseCount('booking_options', 0);
    }

    public function test_a_duplicate_english_label_is_rejected_within_a_list_but_allowed_across_lists(): void
    {
        BookingOption::factory()->faculty()->create(['label_en' => 'Large']);

        $this->admin()->post(route('admin.options.store', 'faculty'), ['label_en' => 'Large', 'label_ms' => 'Besar', 'is_active' => '1'])
            ->assertSessionHasErrors('label_en');

        $this->post(route('admin.options.store', 'robe_size'), ['label_en' => 'Large', 'label_ms' => 'Besar', 'is_active' => '1'])
            ->assertSessionDoesntHaveErrors();
    }

    public function test_an_entry_cannot_be_edited_through_another_lists_url(): void
    {
        $faculty = BookingOption::factory()->faculty()->create();

        $this->admin()->get(route('admin.options.edit', ['convocation_session', $faculty]))->assertNotFound();
        $this->patch(route('admin.options.update', ['convocation_session', $faculty]), [
            'label_en' => 'X', 'label_ms' => 'X', 'is_active' => '1',
        ])->assertNotFound();
    }

    public function test_renaming_shows_up_on_existing_bookings(): void
    {
        $booking = Booking::factory()->create();
        $session = $booking->convocationSession;

        $this->admin()->patch(route('admin.options.update', ['convocation_session', $session]), [
            'label_en' => 'Renamed Session', 'label_ms' => 'Sesi Baharu', 'is_active' => '1',
        ]);

        $this->get(route('admin.bookings.show', $booking))->assertSee('Renamed Session');
    }

    public function test_deactivating_hides_an_entry_from_the_registration_form(): void
    {
        // Every list needs an active entry or /register shows "opens soon" instead of the form.
        BookingOption::factory()->faculty()->create();
        BookingOption::factory()->session()->create();
        BookingOption::factory()->robeSize()->create(['label_en' => 'Medium']);
        $robe = BookingOption::factory()->robeSize()->create(['label_en' => 'Extra Large']);

        $this->get('/register')->assertSee('Extra Large');

        $this->admin()->patch(route('admin.options.update', ['robe_size', $robe]), [
            'label_en' => 'Extra Large', 'label_ms' => 'Sangat Besar', 'is_active' => '0',
        ]);

        $this->assertFalse($robe->fresh()->is_active);
        $this->get('/register')->assertSee('Medium')->assertDontSee('Extra Large');
    }

    public function test_an_unused_entry_can_be_deleted_but_a_used_one_is_refused_with_a_message(): void
    {
        $unused = BookingOption::factory()->faculty()->create();
        $used = Booking::factory()->create()->faculty;

        $this->admin()->delete(route('admin.options.destroy', ['faculty', $unused]))->assertSessionHas('status');
        $this->assertDatabaseMissing('booking_options', ['id' => $unused->id]);

        $this->delete(route('admin.options.destroy', ['faculty', $used]))
            ->assertRedirect(route('admin.options.index', 'faculty'))
            ->assertSessionHas('status', fn (string $status) => str_contains($status, 'Deactivate it instead'));
        $this->assertDatabaseHas('booking_options', ['id' => $used->id]);
    }

    public function test_moving_swaps_positions_within_the_list_only(): void
    {
        $first = BookingOption::factory()->faculty()->create(['position' => 1]);
        $second = BookingOption::factory()->faculty()->create(['position' => 2]);
        $otherList = BookingOption::factory()->robeSize()->create(['position' => 0]);

        $this->admin()->patch(route('admin.options.move', ['faculty', $second, 'up']));

        $this->assertSame(1, $second->fresh()->position);
        $this->assertSame(2, $first->fresh()->position);
        $this->assertSame(0, $otherList->fresh()->position);
    }

    public function test_the_index_shows_how_many_bookings_use_each_entry(): void
    {
        $faculty = Booking::factory()->create()->faculty;
        Booking::factory()->create(['faculty_id' => $faculty->id]);

        $this->admin()->get(route('admin.options.index', 'faculty'))
            ->assertOk()
            ->assertSee($faculty->label_en)
            ->assertSeeInOrder([$faculty->label_en, '2']);
    }
}
