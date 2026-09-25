<?php

namespace App\Support;

use App\Mail\BookingConfirmation;
use App\Mail\NewBookingAlert;
use App\Models\Booking;
use Illuminate\Support\Facades\Mail;

/**
 * The emails a new booking triggers. Both are BEST-EFFORT: by the time this runs the booking
 * is saved, so a Resend outage, a bad address or the free tier's 100/day cap must never turn
 * a saved booking into an error page ("your booking was NOT saved" would then be a lie). This
 * is the one place rescue() is right on the write path: the write already succeeded.
 */
class BookingMailer
{
    public static function sendFor(Booking $booking): void
    {
        $booking->loadMissing(['faculty', 'robeSize', 'convocationSession']);

        if ($booking->email) {
            rescue(fn () => Mail::to($booking->email)
                ->locale($booking->locale)
                ->send(new BookingConfirmation($booking)), report: true);
        }

        // One message to every team address, not one each: Resend's free tier counts messages.
        $team = static::teamRecipients();
        if ($team) {
            rescue(fn () => Mail::to($team)
                ->locale('en')
                ->send(new NewBookingAlert($booking)), report: true);
        }
    }

    /** JP_EMAIL plus JP_NOTIFY_EMAILS, de-duplicated. @return list<string> */
    public static function teamRecipients(): array
    {
        return collect([config('jubahrunner.email'), ...config('jubahrunner.notify_emails', [])])
            ->filter()
            ->map(fn (string $address) => strtolower(trim($address)))
            ->unique()
            ->values()
            ->all();
    }
}
