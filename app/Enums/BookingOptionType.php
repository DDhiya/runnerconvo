<?php

namespace App\Enums;

/** The three admin-managed lists that live in the booking_options table. */
enum BookingOptionType: string
{
    case Faculty = 'faculty';
    case RobeSize = 'robe_size';
    case ConvocationSession = 'convocation_session';

    /** Heading for the admin index and nav. */
    public function label(): string
    {
        return match ($this) {
            self::Faculty => 'Faculties',
            self::RobeSize => 'Robe sizes',
            self::ConvocationSession => 'Sessions',
        };
    }

    public function singular(): string
    {
        return match ($this) {
            self::Faculty => 'faculty',
            self::RobeSize => 'robe size',
            self::ConvocationSession => 'convocation session',
        };
    }

    /** The bookings foreign-key column that points at options of this type. */
    public function column(): string
    {
        return $this->value.'_id';
    }
}
