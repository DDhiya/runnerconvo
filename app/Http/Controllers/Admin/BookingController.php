<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingOptionType;
use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookingUpdateRequest;
use App\Models\Booking;
use App\Models\BookingOption;
use App\Models\Runner;
use App\Support\BookingFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $filters = BookingFilters::fromRequest($request);

        $bookings = $filters->apply(Booking::query())
            ->with(['faculty', 'robeSize', 'convocationSession', 'runner'])
            ->latest()->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'filters' => $filters,
            // Unfiltered totals for the status chips.
            'counts' => Booking::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            // The filter selects list inactive entries too, so an old season stays filterable.
            'faculties' => BookingOption::query()->ofType(BookingOptionType::Faculty)->ordered()->get(),
            'sessions' => BookingOption::query()->ofType(BookingOptionType::ConvocationSession)->ordered()->get(),
            'runners' => Runner::ordered()->get(),
        ]);
    }

    public function show(Booking $booking): View
    {
        // Active options plus the one this booking already has, so saving the form never
        // silently swaps a deactivated session or unassigns an inactive runner.
        $options = fn (BookingOptionType $type) => BookingOption::query()
            ->ofType($type)
            ->where(fn ($q) => $q->where('is_active', true)->orWhere('id', $booking->{$type->column()}))
            ->ordered()->get();

        return view('admin.bookings.show', [
            'booking' => $booking->load('runner'),
            'faculties' => $options(BookingOptionType::Faculty),
            'robeSizes' => $options(BookingOptionType::RobeSize),
            'sessions' => $options(BookingOptionType::ConvocationSession),
            'runners' => Runner::query()
                ->where(fn ($q) => $q->where('is_active', true)->orWhere('id', $booking->runner_id))
                ->ordered()->get(),
            'statuses' => BookingStatus::cases(),
        ]);
    }

    public function update(BookingUpdateRequest $request, Booking $booking): RedirectResponse
    {
        $booking->fill($request->safe()->except(['status', 'paid', 'amount']));

        $status = BookingStatus::from($request->validated('status'));
        if ($status !== $booking->status) {
            $booking->moveTo($status);
        }

        $booking->amount_sen = (int) round($request->validated('amount') * 100);

        if ($request->boolean('paid')) {
            $booking->paid_at ??= now();
        } else {
            // Unticking "Paid" clears the payment details with it.
            $booking->paid_at = null;
            $booking->payment_method = null;
            $booking->payment_reference = null;
        }

        $booking->save();

        return to_route('admin.bookings.show', $booking)->with('status', 'Booking updated.');
    }

    public function destroy(Booking $booking): RedirectResponse
    {
        // A real delete: this is the PDPA erasure / consent-withdrawal path. Cancelling keeps
        // the row; this does not.
        $booking->delete();

        return to_route('admin.bookings.index')->with('status', 'Booking '.$booking->reference.' deleted.');
    }
}
