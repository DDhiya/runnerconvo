{{-- Admin copy is hardcoded English on purpose (see admin/runners/_form.blade.php). --}}
@extends('admin.layout')

@section('title', $type->label())

@section('content')
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-xl font-bold text-ink">{{ $type->label() }}</h1>
        <a href="{{ route('admin.options.create', $type->value) }}" class="btn-primary !px-5 !py-2.5">Add {{ $type->singular() }}</a>
    </div>

    <p class="mt-3 max-w-2xl text-sm text-ink-soft">
        Shown in this order on the registration form, in the language of the graduate.
        An entry that bookings use can only be deactivated, not deleted. Registration stays on
        "opens soon" until every list has at least one active entry.
    </p>

    <div class="glass-card mt-6 overflow-x-auto">
        <table class="w-full min-w-[40rem] text-left text-sm">
            <thead class="bg-white/60 text-xs font-bold tracking-wide text-ink-muted uppercase">
                <tr>
                    <th class="px-5 py-3">English</th>
                    <th class="px-5 py-3">Malay</th>
                    <th class="px-5 py-3">Bookings</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3">Order</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-black/5">
                @forelse ($options as $option)
                    @php $used = $usage[$option->id] ?? 0; @endphp
                    <tr>
                        <td class="px-5 py-3.5 font-semibold text-ink">{{ $option->label_en }}</td>
                        <td class="px-5 py-3.5 text-ink-soft">{{ $option->label_ms }}</td>
                        <td class="px-5 py-3.5 text-ink-soft">{{ $used }}</td>
                        <td class="px-5 py-3.5">
                            @if ($option->is_active)
                                <span class="rounded-full bg-brand-50 px-2.5 py-1 text-[11px] font-bold tracking-wide text-brand-700 uppercase ring-1 ring-brand-100">Active</span>
                            @else
                                <span class="rounded-full bg-black/5 px-2.5 py-1 text-[11px] font-bold tracking-wide text-ink-muted uppercase ring-1 ring-black/10">Inactive</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-1">
                                @foreach (['up' => '&uarr;', 'down' => '&darr;'] as $direction => $arrow)
                                    <form method="POST" action="{{ route('admin.options.move', [$type->value, $option, $direction]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" aria-label="Move {{ $option->label_en }} {{ $direction }}"
                                                class="grid h-7 w-7 place-items-center rounded-lg text-ink-soft transition hover:bg-black/5">{!! $arrow !!}</button>
                                    </form>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('admin.options.edit', [$type->value, $option]) }}" class="font-semibold text-brand-600 transition hover:text-brand-700">Edit</a>
                                @if ($used === 0)
                                    <form method="POST" action="{{ route('admin.options.destroy', [$type->value, $option]) }}"
                                          onsubmit="return confirm('Delete {{ $option->label_en }}? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="font-semibold text-ink-muted transition hover:text-brand-700">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-ink-muted">Nothing here yet. Add the first {{ $type->singular() }}.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
