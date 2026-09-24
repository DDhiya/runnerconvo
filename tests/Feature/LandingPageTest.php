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
}
