{{-- Admin copy is hardcoded English on purpose (see admin/runners/_form.blade.php). --}}
@extends('admin.layout')

@section('title', 'Bookings')

@section('content')
    @php
        $active = $filters->active();
        $select = 'w-full rounded-xl bg-white/70 px-3 py-2 text-sm text-ink ring-1 ring-black/10 focus:ring-2 focus:ring-brand-500 focus:outline-none';
        $total = $counts->sum();
    @endphp

    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-xl font-bold text-ink">Bookings <span class="ml-1 text-sm font-medium text-ink-muted">{{ $bookings->total() }}</span></h1>
        <a href="{{ route('admin.bookings.export', $active) }}" class="btn-ghost !px-5 !py-2.5">Export CSV</a>
    </div>

    {{-- Status chips: unfiltered totals; each one narrows the list to that status. --}}
    <div class="mt-5 flex flex-wrap gap-2 text-xs font-bold">
        @php $withoutStatus = collect($active)->except('status')->all(); @endphp
        <a href="{{ route('admin.bookings.index', $withoutStatus) }}"
           class="rounded-full px-3 py-1.5 ring-1 {{ ! $filters->get('status') ? 'bg-brand-600 text-white ring-brand-600' : 'bg-white/70 text-ink-soft ring-black/10 hover:text-brand-700' }}">
            All {{ $total }}
        </a>
        @foreach (\App\Enums\BookingStatus::cases() as $status)
            <a href="{{ route('admin.bookings.index', [...$withoutStatus, 'status' => $status->value]) }}"
               class="rounded-full px-3 py-1.5 ring-1 {{ $filters->get('status') === $status->value ? 'bg-brand-600 text-white ring-brand-600' : 'bg-white/70 text-ink-soft ring-black/10 hover:text-brand-700' }}">
                {{ $status->label() }} {{ $counts[$status->value] ?? 0 }}
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('admin.bookings.index') }}" class="glass-card mt-5 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
        @if ($filters->get('status'))
            <input type="hidden" name="status" value="{{ $filters->get('status') }}">
        @endif

        <input type="search" name="q" value="{{ $filters->get('q') }}" placeholder="Search reference, name, matric, phone"
               aria-label="Search" class="{{ $select }} sm:col-span-2">

        <select name="convocation_session_id" aria-label="Session" class="{{ $select }}">
            <option value="">All sessions</option>
            @foreach ($sessions as $option)
                <option value="{{ $option->id }}" @selected($filters->get('convocation_session_id') === $option->id)>{{ $option->label_en }}@unless ($option->is_active) (inactive)@endunless</option>
            @endforeach
        </select>

        <select name="faculty_id" aria-label="Faculty" class="{{ $select }}">
            <option value="">All faculties</option>
            @foreach ($faculties as $option)
                <option value="{{ $option->id }}" @selected($filters->get('faculty_id') === $option->id)>{{ $option->label_en }}@unless ($option->is_active) (inactive)@endunless</option>
            @endforeach
        </select>

        <select name="delivery_method" aria-label="Delivery" class="{{ $select }}">
            <option value="">Any delivery</option>
            <option value="pickup" @selected($filters->get('delivery_method') === 'pickup')>Self-pickup</option>
            <option value="cod" @selected($filters->get('delivery_method') === 'cod')>Kuantan COD</option>
        </select>

        <select name="runner_id" aria-label="Runner" class="{{ $select }}">
            <option value="">Any runner</option>
            <option value="none" @selected($filters->get('runner_id') === 'none')>Unassigned</option>
            @foreach ($runners as $runner)
                <option value="{{ $runner->id }}" @selected($filters->get('runner_id') === $runner->id)>{{ $runner->name }}</option>
            @endforeach
        </select>

        <select name="paid" aria-label="Payment" class="{{ $select }}">
            <option value="">Paid or unpaid</option>
            <option value="yes" @selected($filters->get('paid') === 'yes')>Paid</option>
            <option value="no" @selected($filters->get('paid') === 'no')>Unpaid</option>
        </select>

        <div class="flex items-center gap-3 sm:col-span-2 lg:col-span-1">
            <button type="submit" class="btn-primary !px-5 !py-2">Filter</button>
            @if ($active)
                <a href="{{ route('admin.bookings.index') }}" class="text-sm font-semibold text-ink-soft transition hover:text-brand-700">Clear</a>
            @endif
        </div>
    </form>

    <div class="glass-card mt-6 overflow-x-auto">
        <table class="w-full min-w-[56rem] text-left text-sm">
            <thead class="bg-white/60 text-xs font-bold tracking-wide text-ink-muted uppercase">
                <tr>
                    <th class="px-4 py-3">Reference</th>
                    <th class="px-4 py-3">Graduate</th>
                    <th class="px-4 py-3">Session</th>
                    <th class="px-4 py-3">Size</th>
                    <th class="px-4 py-3">Delivery</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Paid</th>
                    <th class="px-4 py-3">Runner</th>
                    <th class="px-4 py-3">Created</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-black/5">
                @forelse ($bookings as $booking)
                    <tr>
                        <td class="px-4 py-3 font-semibold">
                            <a href="{{ route('admin.bookings.show', $booking) }}" class="text-brand-600 transition hover:text-brand-700">{{ $booking->reference }}</a>
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-semibold text-ink">{{ $booking->full_name }}</span>
                            <span class="block text-xs text-ink-muted">{{ $booking->matric_no }}</span>
                        </td>
                        <td class="px-4 py-3 text-ink-soft">{{ $booking->convocationSession->label_en }}</td>
                        <td class="px-4 py-3 text-ink-soft">{{ $booking->robeSize->label_en }}</td>
                        <td class="px-4 py-3 text-ink-soft">{{ $booking->isCod() ? 'Kuantan COD' : 'Self-pickup' }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$booking->status" /></td>
                        <td class="px-4 py-3 text-ink-soft">{{ $booking->isPaid() ? 'Yes' : 'No' }}</td>
                        <td class="px-4 py-3 text-ink-soft">{{ $booking->runner?->name ?? '—' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-ink-muted">{{ $booking->created_at->timezone(config('jubahrunner.timezone'))->format('j M, H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-10 text-center text-ink-muted">No bookings match.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $bookings->links() }}</div>
@endsection
