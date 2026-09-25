<?php

namespace App\Enums;

/**
 * Fulfilment stages. Payment is deliberately NOT a stage: Kuantan cash-on-delivery
 * pays at handover, so "paid" is a flag (bookings.paid_at) beside this line.
 *
 * submitted -> confirmed -> collected -> handed_over, with cancelled reachable from anywhere.
 */
enum BookingStatus: string
{
    case Submitted = 'submitted';
    case Confirmed = 'confirmed';
    case Collected = 'collected';
    case HandedOver = 'handed_over';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::Confirmed => 'Confirmed',
            self::Collected => 'Collected',
            self::HandedOver => 'Handed over',
            self::Cancelled => 'Cancelled',
        };
    }

    /** The bookings column stamped the first time a booking enters this stage. */
    public function timestampColumn(): ?string
    {
        return match ($this) {
            self::Submitted => null,
            self::Confirmed => 'confirmed_at',
            self::Collected => 'collected_at',
            self::HandedOver => 'handed_over_at',
            self::Cancelled => 'cancelled_at',
        };
    }
}
