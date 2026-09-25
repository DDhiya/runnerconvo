{{-- Admin copy is hardcoded English on purpose (see admin/runners/_form.blade.php). --}}
@extends('admin.layout')

@section('title', $booking->reference)

@section('content')
    @php
        $input = 'mt-1.5 w-full rounded-xl bg-white/70 px-4 py-2.5 text-sm text-ink ring-1 ring-black/10 focus:ring-2 focus:ring-brand-500 focus:outline-none';
        $heading = 'text-xs font-bold tracking-wide text-ink-muted uppercase';
        $tz = config('jubahrunner.timezone');
    @endphp

    <a href="{{ route('admin.bookings.index') }}" class="text-sm font-semibold text-ink-soft transition hover:text-brand-700">&larr; All bookings</a>

    <div class="mt-3 flex flex-wrap items-center gap-3">
        <h1 class="text-xl font-bold text-ink">{{ $booking->reference }}</h1>
        <x-status-badge :status="$booking->status" />
    </div>

    @if ($errors->any())
        <div role="alert" class="mt-5 rounded-xl bg-brand-50 px-4 py-3 text-sm text-brand-800 ring-1 ring-brand-200">
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_20rem]">

        <form method="POST" action="{{ route('admin.bookings.update', $booking) }}" class="glass-card space-y-8 p-6 sm:p-8">
            @csrf
            @method('PATCH')

            @include('admin.bookings._graduate')
            @include('admin.bookings._handling')

            <button type="submit" class="btn-primary">Save changes</button>
        </form>

        @include('admin.bookings._sidebar')
    </div>
@endsection
