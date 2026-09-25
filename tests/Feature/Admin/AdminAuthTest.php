<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        // Regression test: without AppServiceProvider's Authenticate::redirectUsing()
        // callback, Laravel 13 returns a blank 401 here instead of a redirect.
        $this->get(route('admin.runners.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_the_login_screen_can_be_rendered(): void
    {
        $this->get(route('admin.login'))->assertStatus(200);
    }

    public function test_an_admin_can_log_in(): void
    {
        $user = User::factory()->create(['password' => Hash::make('correct-password')]);

        $response = $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('admin.bookings.index'));
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => Hash::make('correct-password')]);

        $response = $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_an_admin_can_log_out(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.logout'));

        $this->assertGuest();
        $response->assertRedirect(route('admin.login'));
    }

    public function test_a_logged_in_admin_visiting_the_login_page_goes_to_the_dashboard(): void
    {
        // Regression test: without RedirectIfAuthenticated::redirectUsing(), the
        // default scan lands on the `home` route — the public marketing page.
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.login'))
            ->assertRedirect(route('admin.bookings.index'));
    }
}
