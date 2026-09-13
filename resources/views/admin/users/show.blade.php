@extends('layouts.admin')

@section('title', $user->name)

@section('content')
<div class="flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="font-display text-3xl">{{ $user->name }}</h1>
        <p class="mt-1 text-sm text-nt-ink/60">{{ $user->email }} · {{ $user->role?->label() }}</p>
    </div>
    <a href="{{ route('admin.users.index') }}" class="nt-btn-secondary">Liste</a>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <div class="nt-card">
        <h2 class="font-display text-lg">Rôle plateforme</h2>
        @if (auth()->id() === $user->id)
            <p class="mt-3 text-sm text-nt-ink/60">Vous ne pouvez pas modifier votre propre rôle.</p>
        @else
            <form method="POST" action="{{ route('admin.users.role', $user) }}" class="mt-4 flex flex-wrap gap-2">
                @csrf
                <select name="role" class="nt-field max-w-xs" required>
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected($user->role === $role)>{{ $role->label() }}</option>
                    @endforeach
                </select>
                <button class="nt-btn" type="submit">Assigner</button>
            </form>
        @endif
    </div>

    <div class="nt-card">
        <h2 class="font-display text-lg">Organisations</h2>
        <ul class="mt-3 divide-y divide-[var(--nt-line)] text-sm">
            @forelse ($user->organizations as $organization)
                <li class="py-2">
                    <a href="{{ route('admin.organizations.show', $organization) }}" class="font-medium text-emerald-800">{{ $organization->name }}</a>
                    <span class="text-nt-ink/55"> — {{ $organization->type->label() }}</span>
                </li>
            @empty
                <li class="py-2 text-nt-ink/55">Aucune organisation.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
