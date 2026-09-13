@extends('layouts.admin')

@section('title', $organization->name)

@section('content')
<div class="flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="font-display text-3xl">{{ $organization->name }}</h1>
        <p class="mt-1 text-sm text-nt-ink/60">{{ $organization->type->label() }} · {{ $organization->status->label() }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.organizations.edit', $organization) }}" class="nt-btn-secondary">Modifier</a>
        <form method="POST" action="{{ route('admin.organizations.destroy', $organization) }}" onsubmit="return confirm('Archiver cette organisation ?')">
            @csrf @method('DELETE')
            <button class="nt-btn-secondary text-red-700">Archiver</button>
        </form>
    </div>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <div class="nt-card space-y-2 text-sm">
        <h2 class="font-display text-lg">Informations</h2>
        <p>Email — {{ $organization->email ?: '—' }}</p>
        <p>Téléphone — {{ $organization->phone ?: '—' }}</p>
        <p>Ville — {{ $organization->primaryLocation?->city ?: '—' }}</p>
        <p>Vérifiée le — {{ $organization->verified_at?->format('d/m/Y H:i') ?: '—' }}</p>
        <p>Par — {{ $organization->verifier?->name ?: '—' }}</p>
    </div>

    <div class="nt-card">
        <h2 class="font-display text-lg">Changer le statut</h2>
        <form method="POST" action="{{ route('admin.organizations.verify', $organization) }}" class="mt-4 flex flex-wrap gap-2">
            @csrf
            <select name="status" class="nt-field max-w-xs" required>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected($organization->status === $status)>{{ $status->label() }}</option>
                @endforeach
            </select>
            <button class="nt-btn" type="submit">Appliquer</button>
        </form>

        <h2 class="mt-8 font-display text-lg">Membres ({{ $organization->users->count() }})</h2>
        <ul class="mt-3 divide-y divide-[var(--nt-line)] text-sm">
            @foreach ($organization->users as $member)
                <li class="py-2">
                    <a href="{{ route('admin.users.show', $member) }}" class="font-medium text-emerald-800">{{ $member->name }}</a>
                    <span class="text-nt-ink/55"> — {{ $member->email }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endsection
