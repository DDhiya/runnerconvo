<?php

namespace App\Http\Controllers;

use App\Enums\BookingOptionType;
use App\Enums\BookingStatus;
use App\Http\Requests\RegistrationRequest;
use App\Models\Booking;
use App\Models\BookingOption;
use App\Support\BookingMailer;
use App\Support\RegistrationWindow;
use Illuminate\Contracts\View\View;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

use function Illuminate\Support\defer;

class RegistrationController extends Controller
{
    public function create(): View
    {
        $state = RegistrationWindow::state();

        $options = [];
        if ($state === RegistrationWindow::OPEN) {
            foreach (BookingOptionType::cases() as $type) {
                $options[$type->value] = BookingOption::query()->ofType($type)->active()->ordered()->get();
            }
        }

        return view('register.create', [
            'state' => $state,
            'options' => $options,
            'closesAt' => RegistrationWindow::closesAt(),
        ]);
    }

    public function store(RegistrationRequest $request): RedirectResponse
    {
        if (! RegistrationWindow::isOpen()) {
            return to_route('register');
        }

        // NO rescue() here, unlike LandingController. A write that fails quietly leaves a
        // graduate believing they have booked. Let it 500: errors/500 says "not saved".
        try {
            $booking = Booking::create([
                ...$request->safe()->except(['documents_ack', 'consent']),
                'status' => BookingStatus::Submitted,
                'amount_sen' => config('jubahrunner.price_sen'),
                'consented_at' => now(),
                'privacy_version' => config('jubahrunner.privacy_version'),
                'locale' => app()->getLocale(),
            ]);
        } catch (UniqueConstraintViolationException $e) {
            // Two submits raced past Rule::unique and the partial index caught the second.
            // Only translate the matric index; anything else is a real bug and should 500.
            throw_unless(str_contains($e->getMessage(), 'matric_no'), $e);

            throw ValidationException::withMessages(['matric_no' => __('register.errors.duplicate')]);
        }

        // put(), not flash(): a refresh of the done page must still work.
        $request->session()->put('registration.reference', $booking->reference);

        // After the response is sent, so a slow mail provider never delays the done page.
        // BookingMailer swallows (and reports) its own failures: the booking is already saved.
        defer(fn () => BookingMailer::sendFor($booking));

        return to_route('register.done');
    }

    public function show(Request $request): View|RedirectResponse
    {
        $reference = $request->session()->get('registration.reference');

        $booking = $reference
            ? Booking::query()->with('convocationSession')->where('reference', $reference)->first()
            : null;

        // No session, or the booking was deleted since (e.g. an erasure request).
        if (! $booking) {
            return to_route('register');
        }

        return view('register.done', ['booking' => $booking]);
    }
}
