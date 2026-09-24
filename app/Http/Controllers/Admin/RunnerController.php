<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RunnerRequest;
use App\Models\Runner;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class RunnerController extends Controller
{
    public function index(): View
    {
        // No active() scope — the admin must see deactivated runners to reactivate them.
        return view('admin.runners.index', ['runners' => Runner::ordered()->get()]);
    }

    public function create(): View
    {
        return view('admin.runners.create', ['runner' => new Runner]);
    }

    public function store(RunnerRequest $request): RedirectResponse
    {
        Runner::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active'),
            // Append to the end. max() over the whole table (not just active) keeps
            // positions a single unbroken sequence.
            'position' => (int) Runner::max('position') + 1,
        ]);

        return to_route('admin.runners.index')->with('status', 'Runner added.');
    }

    public function edit(Runner $runner): View
    {
        return view('admin.runners.edit', ['runner' => $runner]);
    }

    public function update(RunnerRequest $request, Runner $runner): RedirectResponse
    {
        $runner->update([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active'),
        ]);

        return to_route('admin.runners.index')->with('status', 'Runner updated.');
    }

    public function destroy(Runner $runner): RedirectResponse
    {
        // Real delete, alongside the is_active toggle: deactivate is the everyday
        // action, delete is for "I typed it in twice". Leaves a gap in `position`
        // (e.g. 1,2,4,5) — harmless, since ordering is by value, not by index.
        $runner->delete();

        return to_route('admin.runners.index')->with('status', 'Runner deleted.');
    }
}
