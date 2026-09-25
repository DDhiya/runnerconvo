<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingOptionType;
use App\Http\Controllers\Controller;
use App\Models\BookingOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class BookingOptionOrderController extends Controller
{
    /**
     * Swap an option's position with its nearest neighbour IN THE SAME LIST. Same trick as
     * RunnerOrderController: it only works because `position` has no unique index.
     */
    public function __invoke(string $type, BookingOption $option, string $direction): RedirectResponse
    {
        $type = BookingOptionType::tryFrom($type) ?? abort(404);
        abort_unless($option->type === $type, 404);

        DB::transaction(function () use ($option, $type, $direction) {
            $neighbour = BookingOption::query()
                ->ofType($type)
                ->when(
                    $direction === 'up',
                    fn ($query) => $query->where('position', '<', $option->position)->orderByDesc('position'),
                    fn ($query) => $query->where('position', '>', $option->position)->orderBy('position'),
                )
                ->first();

            if (! $neighbour) {
                return; // Already at that end of the list.
            }

            [$option->position, $neighbour->position] = [$neighbour->position, $option->position];
            $option->save();
            $neighbour->save();
        });

        return back()->with('status', 'Order updated.');
    }
}
