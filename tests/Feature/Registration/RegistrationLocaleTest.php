<?php

namespace Tests\Feature\Registration;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationLocaleTest extends TestCase
{
    use RefreshDatabase;
    use RegistrationPayload;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedOptions();
    }

    public function test_validation_errors_on_a_malay_page_are_in_malay_with_malay_field_names(): void
    {
        $this->withSession(['locale' => 'ms'])
            ->post('/register', [])
            ->assertSessionHasErrors(['full_name' => 'Nama penuh wajib diisi.']);
    }

    public function test_custom_messages_are_localised_too(): void
    {
        $this->withSession(['locale' => 'ms'])
            ->post('/register', $this->validPayload(['phone' => '12345']))
            ->assertSessionHasErrors(['phone' => __('register.errors.phone_format', [], 'ms')]);
    }

    public function test_english_pages_get_english_errors(): void
    {
        $this->post('/register', [])->assertSessionHasErrors(['full_name' => 'Full name is required.']);
    }

    public function test_the_admin_stays_english_even_when_the_session_language_is_malay(): void
    {
        // Regression test for AdminLocale: lang/ms/validation.php exists now, so without it
        // an admin who toggled the landing page to BM would get Malay errors in the admin.
        $this->actingAs(User::factory()->create())
            ->withSession(['locale' => 'ms'])
            ->post(route('admin.runners.store'), ['name' => '', 'phone' => '60123456789', 'is_active' => '1'])
            ->assertSessionHasErrors(['name' => 'The name field is required.']);
    }
}
