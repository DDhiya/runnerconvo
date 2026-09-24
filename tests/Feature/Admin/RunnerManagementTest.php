<?php

namespace Tests\Feature\Admin;

use App\Models\Runner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RunnerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_runner_can_be_created(): void
    {
        $response = $this->actingAs(User::factory()->create())->post(route('admin.runners.store'), [
            'name' => 'Hanizam',
            'phone' => '60106554842',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.runners.index'));
        $this->assertDatabaseHas('runners', ['name' => 'Hanizam', 'phone' => '60106554842']);
    }

    public function test_a_phone_number_is_normalised_on_create(): void
    {
        $this->actingAs(User::factory()->create())->post(route('admin.runners.store'), [
            'name' => 'Dhiya',
            'phone' => '+60 14-533 2637',
            'is_active' => '1',
        ]);

        $this->assertDatabaseHas('runners', ['name' => 'Dhiya', 'phone' => '60145332637']);

        $this->actingAs(User::factory()->create())->post(route('admin.runners.store'), [
            'name' => 'Fazil',
            'phone' => '019-921 5166',
            'is_active' => '1',
        ]);

        $this->assertDatabaseHas('runners', ['name' => 'Fazil', 'phone' => '60199215166']);
    }

    public function test_a_duplicate_phone_is_rejected_even_when_formatted_differently(): void
    {
        Runner::factory()->create(['phone' => '60145332637']);

        // Without prepareForValidation() normalising before the unique check, this
        // sails past validation and 500s on the DB constraint instead.
        $response = $this->actingAs(User::factory()->create())->post(route('admin.runners.store'), [
            'name' => 'Duplicate',
            'phone' => '+60 14-533 2637',
            'is_active' => '1',
        ]);

        $response->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('runners', 1);
    }

    public function test_an_invalid_phone_is_rejected(): void
    {
        $user = User::factory()->create();

        foreach (['12345', '601234567', '60312345678'] as $phone) {
            $response = $this->actingAs($user)->post(route('admin.runners.store'), [
                'name' => 'Test',
                'phone' => $phone,
                'is_active' => '1',
            ]);

            $response->assertSessionHasErrors('phone');
        }

        $this->assertDatabaseCount('runners', 0);
    }

    public function test_a_runner_can_be_renamed(): void
    {
        $runner = Runner::factory()->create(['name' => 'Old Name']);

        $this->actingAs(User::factory()->create())->patch(route('admin.runners.update', $runner), [
            'name' => 'New Name',
            'phone' => $runner->phone,
            'is_active' => '1',
        ]);

        $this->assertDatabaseHas('runners', ['id' => $runner->id, 'name' => 'New Name']);
    }

    public function test_a_runner_can_be_deactivated_and_disappears_from_the_landing_page(): void
    {
        $runner = Runner::factory()->create(['name' => 'Hanizam', 'is_active' => true]);

        $this->actingAs(User::factory()->create())->patch(route('admin.runners.update', $runner), [
            'name' => $runner->name,
            'phone' => $runner->phone,
            'is_active' => '0',
        ]);

        $this->assertDatabaseHas('runners', ['id' => $runner->id, 'is_active' => false]);
        $this->get('/')->assertDontSee('Hanizam');
    }

    public function test_moving_a_runner_up_swaps_positions(): void
    {
        $first = Runner::factory()->create(['position' => 1]);
        $second = Runner::factory()->create(['position' => 2]);

        $this->actingAs(User::factory()->create())
            ->patch(route('admin.runners.move', [$second, 'up']));

        $this->assertSame(1, $second->fresh()->position);
        $this->assertSame(2, $first->fresh()->position);
    }

    public function test_moving_the_first_runner_up_is_a_no_op(): void
    {
        $first = Runner::factory()->create(['position' => 1]);

        $this->actingAs(User::factory()->create())
            ->patch(route('admin.runners.move', [$first, 'up']));

        $this->assertSame(1, $first->fresh()->position);
    }

    public function test_guests_cannot_create_runners(): void
    {
        $response = $this->post(route('admin.runners.store'), [
            'name' => 'Nobody',
            'phone' => '60106554842',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.login'));
        $this->assertDatabaseCount('runners', 0);
    }
}
