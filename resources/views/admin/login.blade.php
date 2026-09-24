@extends('admin.layout')

@section('title', 'Log in')

@section('content')
    <div class="mx-auto max-w-sm">
        <div class="glass-card p-8">
            <h1 class="text-lg font-semibold text-ink">Admin login</h1>

            <form method="POST" action="{{ route('admin.login.store') }}" class="mt-6 space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-bold tracking-wide text-ink-muted uppercase">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="mt-1.5 w-full rounded-xl bg-white/70 px-4 py-2.5 text-sm text-ink ring-1 ring-black/10 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    @error('email')
                        <p class="mt-1.5 text-xs text-brand-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold tracking-wide text-ink-muted uppercase">Password</label>
                    <input id="password" type="password" name="password" required
                           class="mt-1.5 w-full rounded-xl bg-white/70 px-4 py-2.5 text-sm text-ink ring-1 ring-black/10 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                </div>

                <label class="flex items-center gap-2 text-sm text-ink-soft">
                    <input type="checkbox" name="remember" class="rounded border-black/20 text-brand-600 focus:ring-brand-500">
                    Remember me
                </label>

                <button type="submit" class="btn-primary w-full">Log in</button>
            </form>
        </div>
    </div>
@endsection
