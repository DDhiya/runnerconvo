<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Sent to the team (JP_EMAIL plus JP_NOTIFY_EMAILS) for every new booking, so nobody has to
 * keep /admin open. English, like the rest of the admin. It carries personal data, which is
 * why the privacy notice names the team mailbox and the mail provider.
 */
class NewBookingAlert extends Mailable
{
    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        $b = $this->booking;

        return new Envelope(
            // "Reply" goes straight to the graduate when they gave an email.
            replyTo: $b->email ? [new Address($b->email, $b->full_name)] : [],
            subject: "New booking {$b->reference}: {$b->full_name} ({$b->convocationSession->label_en})",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.new-booking-alert');
    }
}
