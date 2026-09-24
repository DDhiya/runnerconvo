<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Runner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class RunnerOrderController extends Controller
{
    /**
     * Swap a runner's position with its nearest neighbour in the given direction.
     *
     * This only works because `position` has no unique index — the first save()
     * inside the transaction would otherwise collide with the neighbour's old value.
     * Deliberately does not filter by is_active, so activating a runner never makes
     * it jump in the order.
     */
    public function __invoke(Runner $runner, string $direction): RedirectResponse
    {
        DB::transaction(function () use ($runner, $direction) {
            $neighbour = Runner::query()
                ->when(
                    $direction === 'up',
                    fn ($query) => $query->where('position', '<', $runner->position)->orderByDesc('position'),
                    fn ($query) => $query->where('position', '>', $runner->position)->orderBy('position'),
                )
                ->first();

            if (! $neighbour) {
                return; // Already at that end of the list.
            }

            [$runner->position, $neighbour->position] = [$neighbour->position, $runner->position];
            $runner->save();
            $neighbour->save();
        });

        return back()->with('status', 'Order updated.');
    }
}
