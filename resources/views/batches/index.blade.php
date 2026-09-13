@extends('layouts.front')

@section('title', 'Lots')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="nt-kicker">Traçabilité</p>
        <h1 class="nt-page-title mt-1">Lots</h1>
        <p class="nt-page-sub">Identifiants QR et historique de chaîne.</p>
    </div>
    <a href="{{ route('products.index') }}" class="nt-btn-secondary">Via un produit →</a>
</div>

<form method="GET" class="mt-6 flex gap-2">
    <input type="search" name="q" value="{{ request('q') }}" class="nt-field max-w-sm" placeholder="Code lot ou produit">
    <button class="nt-btn-secondary">Filtrer</button>
</form>

<div class="mt-6 grid gap-3">
    @forelse ($batches as $batch)
        <a href="{{ route('batches.show', $batch) }}" class="nt-card block transition hover:-translate-y-0.5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-display text-xl font-semibold">{{ $batch->code }}</h2>
                    <p class="mt-1 text-sm text-nt-ink/60">
                        {{ $batch->product?->name }} · {{ $batch->organization?->name }}
                        · {{ $batch->quantity }} {{ $batch->unit }}
                    </p>
                </div>
                <span class="rounded-full bg-nt-mist px-3 py-1 text-xs font-medium">{{ $batch->status->value }}</span>
            </div>
        </a>
    @empty
        <div class="nt-card text-sm text-nt-ink/70">Aucun lot.</div>
    @endforelse
</div>
<div class="mt-6">{{ $batches->links() }}</div>
@endsection
