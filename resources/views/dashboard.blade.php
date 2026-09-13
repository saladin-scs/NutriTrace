@extends('layouts.front')

@section('title', 'Dashboard')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4 nt-reveal">
    <div>
        <p class="nt-kicker">Espace acteur</p>
        <h1 class="nt-page-title mt-1">Bonjour, {{ auth()->user()->name }}</h1>
        <p class="nt-page-sub">Traçabilité, lots et flux de distribution.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('products.create') }}" class="nt-btn">+ Produit</a>
        <a href="{{ route('cold-rooms.index') }}" class="nt-btn-secondary">Chambres froides</a>
        <a href="{{ route('batches.index') }}" class="nt-btn-secondary">Lots</a>
    </div>
</div>

<div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="nt-stat">
        <p class="nt-kicker">Organisations</p>
        <p class="nt-stat-value">{{ $organizationCount }}</p>
    </div>
    <div class="nt-stat">
        <p class="nt-kicker">Produits</p>
        <p class="nt-stat-value">{{ $productCount }}</p>
    </div>
    <div class="nt-stat">
        <p class="nt-kicker">Lots</p>
        <p class="nt-stat-value">{{ $batchCount }}</p>
    </div>
    <div class="nt-stat">
        <p class="nt-kicker">Chambres froides</p>
        <p class="nt-stat-value">{{ $coldRoomCount }}</p>
    </div>
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-2">
    <div class="nt-card">
        <div class="flex items-center justify-between gap-2">
            <h2 class="font-display text-xl font-semibold">Lots récents</h2>
            <a href="{{ route('batches.index') }}" class="nt-link text-sm">Tout voir</a>
        </div>
        <ul class="mt-4 divide-y divide-[var(--nt-line)] text-sm">
            @forelse ($recentBatches as $batch)
                <li class="py-3">
                    <a href="{{ route('batches.show', $batch) }}" class="font-medium text-emerald-800">{{ $batch->code }}</a>
                    <p class="text-nt-ink/55">{{ $batch->product?->name }} · {{ $batch->organization?->name }}</p>
                </li>
            @empty
                <li class="py-3 text-nt-ink/55">Aucun lot. Créez un produit puis un lot.</li>
            @endforelse
        </ul>
    </div>

    <div class="nt-card">
        <div class="flex items-center justify-between gap-2">
            <h2 class="font-display text-xl font-semibold">Flux chambres froides</h2>
            <a href="{{ route('cold-rooms.index') }}" class="nt-link text-sm">Ouvrir</a>
        </div>
        <ul class="mt-4 divide-y divide-[var(--nt-line)] text-sm">
            @forelse ($recentMovements as $movement)
                <li class="py-3">
                    <p class="font-medium">{{ $movement->event_label ?: $movement->type->label() }}</p>
                    <p class="text-nt-ink/55">
                        {{ $movement->coldRoom?->name }}
                        @if ($movement->batch) · {{ $movement->batch->code }} @endif
                        · {{ $movement->occurred_at?->format('d/m H:i') }}
                    </p>
                </li>
            @empty
                <li class="py-3 text-nt-ink/55">Aucun flux enregistré.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
