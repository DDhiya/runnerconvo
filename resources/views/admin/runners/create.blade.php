@extends('admin.layout')

@section('title', 'Add runner')

@section('content')
    <h1 class="text-xl font-bold text-ink">Add runner</h1>

    <div class="glass-card mt-6 max-w-lg p-8">
        <form method="POST" action="{{ route('admin.runners.store') }}">
            @csrf

            @include('admin.runners._form', ['runner' => $runner])

            <div class="mt-6 flex items-center gap-3">
                <button type="submit" class="btn-primary">Add runner</button>
                <a href="{{ route('admin.runners.index') }}" class="text-sm font-semibold text-ink-soft transition hover:text-brand-700">Cancel</a>
            </div>
        </form>
    </div>
@endsection
