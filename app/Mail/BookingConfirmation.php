<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Sent to the graduate, only when they gave an (optional) email. Rendered in the language
 * they registered in: the caller sets ->locale($booking->locale).
 */
class BookingConfirmation extends Mailable
{
    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            // From is MAIL_FROM_ADDRESS (support@); replies land in the team mailbox either way.
            replyTo: [new Address(config('jubahrunner.email'), config('app.name'))],
            subject: __('mail.confirmation.subject', ['reference' => $this->booking->reference]),
        );
    }

    public function content(): Content
    {
        $message = __('register.done.whatsapp_message', [
            'name' => $this->booking->full_name,
            'reference' => $this->booking->reference,
        ]);

        return new Content(
            markdown: 'mail.booking-confirmation',
            with: [
                'whatsappUrl' => config('jubahrunner.whatsapp_url').'?text='.rawurlencode($message),
            ],
        );
    }
}
