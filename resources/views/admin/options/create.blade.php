@extends('admin.layout')

@section('title', 'Add '.$type->singular())

@section('content')
    <h1 class="text-xl font-bold text-ink">Add {{ $type->singular() }}</h1>

    <div class="glass-card mt-6 max-w-lg p-8">
        <form method="POST" action="{{ route('admin.options.store', $type->value) }}">
            @csrf

            @include('admin.options._form', ['option' => $option])

            <div class="mt-6 flex items-center gap-3">
                <button type="submit" class="btn-primary">Add {{ $type->singular() }}</button>
                <a href="{{ route('admin.options.index', $type->value) }}" class="text-sm font-semibold text-ink-soft transition hover:text-brand-700">Cancel</a>
            </div>
        </form>
    </div>
@endsection
