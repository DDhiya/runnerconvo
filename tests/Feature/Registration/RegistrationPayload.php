<?php

namespace Tests\Feature\Registration;

use App\Models\BookingOption;
use Illuminate\Support\Facades\Crypt;

/** Shared setup for the public registration tests. */
trait RegistrationPayload
{
    /** @var array{faculty: BookingOption, robe: BookingOption, session: BookingOption} */
    protected array $options;

    protected function seedOptions(): void
    {
        $this->options = [
            'faculty' => BookingOption::factory()->faculty()->create(['label_en' => 'Computing', 'label_ms' => 'Pengkomputeran']),
            'robe' => BookingOption::factory()->robeSize()->create(['label_en' => 'Medium', 'label_ms' => 'Sederhana']),
            'session' => BookingOption::factory()->session()->create(['label_en' => 'Session 1', 'label_ms' => 'Sesi 1']),
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function validPayload(array $overrides = []): array
    {
        return [
            'full_name' => 'Aisyah Rahman',
            'matric_no' => 'CB22001',
            'phone' => '012-345 6789',
            'programme_level' => 'bachelor',
            'faculty_id' => $this->options['faculty']->id,
            'robe_size_id' => $this->options['robe']->id,
            'convocation_session_id' => $this->options['session']->id,
            'delivery_method' => 'pickup',
            'delivery_address' => '',
            'notes' => '',
            'documents_ack' => '1',
            'consent' => '1',
            'contact_me_by_fax_only' => '',
            // A form loaded a minute ago, comfortably past the 3-second time trap.
            '_started' => Crypt::encryptString((string) now()->subMinute()->timestamp),
            ...$overrides,
        ];
    }
}
