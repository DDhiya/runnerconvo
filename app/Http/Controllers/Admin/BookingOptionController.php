<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingOptionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookingOptionRequest;
use App\Models\Booking;
use App\Models\BookingOption;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class BookingOptionController extends Controller
{
    public function index(string $type): View
    {
        $type = $this->type($type);

        // No active() scope: the admin must see deactivated entries to reactivate them.
        $options = BookingOption::query()->ofType($type)->ordered()->get();

        // How many bookings use each entry. The column comes from the enum, never from input.
        $usage = Booking::query()
            ->selectRaw($type->column().' as option_id, count(*) as total')
            ->groupBy($type->column())
            ->pluck('total', 'option_id');

        return view('admin.options.index', ['type' => $type, 'options' => $options, 'usage' => $usage]);
    }

    public function create(string $type): View
    {
        return view('admin.options.create', ['type' => $this->type($type), 'option' => new BookingOption]);
    }

    public function store(BookingOptionRequest $request, string $type): RedirectResponse
    {
        $type = $this->type($type);

        BookingOption::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active'),
            'type' => $type,
            // Append to the end of THIS list.
            'position' => (int) BookingOption::query()->ofType($type)->max('position') + 1,
        ]);

        return to_route('admin.options.index', $type->value)->with('status', ucfirst($type->singular()).' added.');
    }

    public function edit(string $type, BookingOption $option): View
    {
        $type = $this->type($type);
        abort_unless($option->type === $type, 404);

        return view('admin.options.edit', ['type' => $type, 'option' => $option]);
    }

    public function update(BookingOptionRequest $request, string $type, BookingOption $option): RedirectResponse
    {
        $type = $this->type($type);
        abort_unless($option->type === $type, 404);

        $option->update([...$request->validated(), 'is_active' => $request->boolean('is_active')]);

        return to_route('admin.options.index', $type->value)->with('status', ucfirst($type->singular()).' updated.');
    }

    public function destroy(string $type, BookingOption $option): RedirectResponse
    {
        $type = $this->type($type);
        abort_unless($option->type === $type, 404);

        // restrictOnDelete is the backstop if two admins race; this is the friendly message.
        if ($option->isInUse()) {
            return to_route('admin.options.index', $type->value)
                ->with('status', '"'.$option->label_en.'" is used by bookings, so it cannot be deleted. Deactivate it instead.');
        }

        $option->delete();

        return to_route('admin.options.index', $type->value)->with('status', ucfirst($type->singular()).' deleted.');
    }

    /** An unknown {type} segment is a 404, never a 500. */
    private function type(string $type): BookingOptionType
    {
        return BookingOptionType::tryFrom($type) ?? abort(404);
    }
}
