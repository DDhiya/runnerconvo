<?php

namespace App\Support;

use App\Enums\BookingOptionType;
use App\Models\BookingOption;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * Whether /register accepts bookings. The controller, the views and the tests all
 * go through here so "open" means one thing.
 */
class RegistrationWindow
{
    public const OPEN = 'open';

    public const CLOSED = 'closed';

    /** Not closed by date, but an option list is still empty (admin has not filled it in). */
    public const SOON = 'soon';

    public static function state(): string
    {
        if (static::hasClosed()) {
            return self::CLOSED;
        }

        return static::optionsReady() ? self::OPEN : self::SOON;
    }

    public static function isOpen(): bool
    {
        return static::state() === self::OPEN;
    }

    public static function hasClosed(): bool
    {
        $closesAt = static::closesAt();

        return $closesAt !== null && now()->greaterThanOrEqualTo($closesAt);
    }

    public static function closesAt(): ?CarbonImmutable
    {
        $value = config('jubahrunner.registration_closes_at');

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value, config('jubahrunner.timezone'));
        } catch (Throwable $e) {
            // A typo in .env should close the form loudly, not leave it open forever.
            report($e);

            return CarbonImmutable::createFromTimestamp(0);
        }
    }

    /** Every list needs at least one active entry, or a select would render empty. */
    public static function optionsReady(): bool
    {
        foreach (BookingOptionType::cases() as $type) {
            if (! BookingOption::query()->ofType($type)->active()->exists()) {
                return false;
            }
        }

        return true;
    }
}
