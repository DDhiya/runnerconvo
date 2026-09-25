@extends('admin.layout')

@section('title', 'Runners')

@section('content')
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-xl font-bold text-ink">Runners</h1>
        <a href="{{ route('admin.runners.create') }}" class="btn-primary !px-5 !py-2.5">Add runner</a>
    </div>

    <div class="glass-card mt-6 overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-white/60 text-xs font-bold tracking-wide text-ink-muted uppercase">
                <tr>
                    <th class="px-5 py-3">Name</th>
                    <th class="px-5 py-3">Phone</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3">Order</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-black/5">
                @foreach ($runners as $runner)
                    <tr>
                        <td class="px-5 py-3.5 font-semibold text-ink">{{ $runner->name }}</td>
                        <td class="px-5 py-3.5 text-ink-soft">
                            {{ $runner->phone }}
                            {{-- Distinct from the runners table: the business-line number
                                 that every CTA on the landing page falls back to. --}}
                            @if ($runner->phone === config('jubahrunner.whatsapp_number'))
                                <span class="ml-2 rounded-full bg-accent-100 px-2 py-0.5 text-[10px] font-bold tracking-wide text-accent-600 uppercase ring-1 ring-accent-300/50">
                                    Main line
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            @if ($runner->is_active)
                                <span class="rounded-full bg-brand-50 px-2.5 py-1 text-[11px] font-bold tracking-wide text-brand-700 uppercase ring-1 ring-brand-100">Active</span>
                            @else
                                <span class="rounded-full bg-black/5 px-2.5 py-1 text-[11px] font-bold tracking-wide text-ink-muted uppercase ring-1 ring-black/10">Inactive</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-1">
                                <form method="POST" action="{{ route('admin.runners.move', [$runner, 'up']) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" aria-label="Move {{ $runner->name }} up"
                                            class="grid h-7 w-7 place-items-center rounded-lg text-ink-soft transition hover:bg-black/5">&uarr;</button>
                                </form>
                                <form method="POST" action="{{ route('admin.runners.move', [$runner, 'down']) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" aria-label="Move {{ $runner->name }} down"
                                            class="grid h-7 w-7 place-items-center rounded-lg text-ink-soft transition hover:bg-black/5">&darr;</button>
                                </form>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('admin.runners.edit', $runner) }}" class="font-semibold text-brand-600 transition hover:text-brand-700">Edit</a>
                                <form method="POST" action="{{ route('admin.runners.destroy', $runner) }}"
                                      onsubmit="return confirm('Delete {{ $runner->name }}? This cannot be undone.{{ $runner->bookings_count ? ' '.$runner->bookings_count.' booking(s) assigned to them will become unassigned.' : '' }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-semibold text-ink-muted transition hover:text-brand-700">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
