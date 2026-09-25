<?php

namespace Tests\Feature;

use Tests\TestCase;

class PrivacyPageTest extends TestCase
{
    public function test_the_notice_is_shown_in_both_languages_with_the_current_one_first(): void
    {
        $this->get('/privacy')
            ->assertOk()
            ->assertSeeInOrder(['Privacy notice', 'Notis privasi']);

        $this->withSession(['locale' => 'ms'])->get('/privacy')
            ->assertOk()
            ->assertSeeInOrder(['Notis privasi', 'Privacy notice']);
    }

    public function test_the_notice_names_the_contact_channels_and_where_data_is_stored(): void
    {
        config(['jubahrunner.email' => 'support@jubahpanda.my', 'jubahrunner.whatsapp_number' => '60123456789']);

        $this->get('/privacy')
            ->assertSee('support@jubahpanda.my')
            ->assertSee('+60123456789')
            ->assertSee('Malaysia (Johor)');
    }

    public function test_the_footer_links_to_the_notice(): void
    {
        $this->get('/')->assertSee('href="'.route('privacy').'"', false);
    }
}
