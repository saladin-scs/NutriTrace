@extends('layouts.front')

@section('title', 'Mes organisations')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="font-display text-3xl font-semibold">Organisations</h1>
        <p class="mt-1 text-sm text-nt-ink/60">Gérez vos entités de la chaîne alimentaire.</p>
    </div>
    <a href="{{ route('organizations.create') }}" class="nt-btn">Nouvelle organisation</a>
</div>

<div class="mt-8 grid gap-4">
    @forelse ($organizations as $organization)
        <a href="{{ route('organizations.show', $organization) }}" class="nt-card block transition hover:-translate-y-0.5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-display text-xl font-semibold">{{ $organization->name }}</h2>
                    <p class="mt-1 text-sm text-nt-ink/60">
                        {{ $organization->type->label() }}
                        @if ($organization->primaryLocation?->city)
                            · {{ $organization->primaryLocation->city }}
                        @endif
                    </p>
                </div>
                <span class="rounded-full bg-nt-mist px-3 py-1 text-xs font-medium">{{ $organization->status->label() }}</span>
            </div>
        </a>
    @empty
        <div class="nt-card text-sm text-nt-ink/70">
            Aucune organisation pour le moment.
            <a href="{{ route('organizations.create') }}" class="ml-1 text-emerald-700 underline">Créer la première</a>
        </div>
    @endforelse
</div>

<div class="mt-6">{{ $organizations->links() }}</div>
@endsection
