<?php

namespace Tests\Feature;

use App\Models\Runner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_landing_page_renders(): void
    {
        $this->get('/')->assertStatus(200);
    }

    public function test_active_runners_are_listed_with_a_whatsapp_link(): void
    {
        Runner::factory()->create(['name' => 'Hanizam', 'phone' => '60106554842']);

        $this->get('/')
            ->assertSee('Hanizam')
            ->assertSee('https://wa.me/60106554842', false);
    }

    public function test_inactive_runners_are_hidden(): void
    {
        Runner::factory()->inactive()->create(['name' => 'Ghost']);

        $this->get('/')->assertDontSee('Ghost');
    }

    public function test_runners_render_in_position_order(): void
    {
        Runner::factory()->create(['name' => 'Second', 'position' => 2]);
        Runner::factory()->create(['name' => 'First', 'position' => 1]);

        $this->get('/')->assertSeeInOrder(['First', 'Second']);
    }

    public function test_the_section_is_hidden_when_there_are_no_runners(): void
    {
        $this->get('/')->assertDontSee(__('landing.runners.title'));
    }

    public function test_register_buttons_point_at_the_in_app_form_by_default(): void
    {
        $this->get('/')->assertSee('href="'.route('register').'"', false);
    }

    public function test_register_url_env_override_wins_as_a_kill_switch(): void
    {
        config(['jubahrunner.register_url' => 'https://wa.me/60111111111']);

        $this->get('/')
            ->assertSee('href="https://wa.me/60111111111"', false)
            ->assertDontSee('href="'.route('register').'"', false);
    }

    public function test_nav_links_are_absolute_so_they_work_from_other_pages(): void
    {
        // On /register a bare "#how" would resolve to /register#how and go nowhere.
        $this->get('/register')->assertSee('href="'.route('home').'#how"', false);
    }

    public function test_the_snapshotted_price_matches_the_price_shown_on_the_page(): void
    {
        foreach (['en', 'ms'] as $locale) {
            $shown = (int) preg_replace('/\D/', '', __('landing.pricing.price', [], $locale));

            $this->assertSame(config('jubahrunner.price_sen'), $shown * 100, "pricing.price ({$locale}) has drifted from jubahrunner.price_sen");
        }
    }
}
