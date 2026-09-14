@extends('layouts.front')

@section('title', 'Dashboard')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4 nt-reveal">
    <div>
        <p class="nt-kicker">Espace acteur</p>
        <h1 class="nt-page-title mt-1">Bonjour, {{ auth()->user()->name }}</h1>
        <p class="nt-page-sub">Pilotage ops · Shipments, alertes et cold chain.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('control-tower.index') }}" class="nt-btn">Control Tower</a>
        <a href="{{ route('shipments.create') }}" class="nt-btn-secondary">+ Shipment</a>
    </div>
</div>

{{-- Ops KPIs --}}
<div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <a href="{{ route('shipments.index') }}" class="nt-stat nt-card-interactive block">
        <p class="nt-kicker">Shipments actifs</p>
        <p class="nt-stat-value">{{ $activeShipments }}</p>
    </a>
    <a href="{{ route('alert-center.index') }}" class="nt-stat nt-card-interactive block">
        <p class="nt-kicker">Alertes ouvertes</p>
        <p class="nt-stat-value {{ $openAlerts > 0 ? 'text-rose-700' : '' }}">{{ $openAlerts }}</p>
    </a>
    <a href="{{ route('cold-rooms.index') }}" class="nt-stat nt-card-interactive block">
        <p class="nt-kicker">Chambres froides</p>
        <p class="nt-stat-value">{{ $coldRoomCount }}</p>
    </a>
    <a href="{{ route('control-tower.index', ['tab' => 'intelligence']) }}" class="nt-stat nt-card-interactive block">
        <p class="nt-kicker">Lots suivis</p>
        <p class="nt-stat-value">{{ $batchCount }}</p>
    </a>
</div>

{{-- Ops shortcuts --}}
<div class="mt-6 grid gap-3 sm:grid-cols-3">
    <a href="{{ route('shipments.index') }}" class="rounded-2xl border border-[var(--nt-line)] bg-white/85 px-4 py-4 transition hover:border-emerald-700/30 hover:bg-white">
        <p class="font-display text-base font-semibold">Shipments</p>
        <p class="mt-1 text-sm text-nt-ink/55">Suivi des expéditions et retards</p>
    </a>
    <a href="{{ route('alert-center.index') }}" class="rounded-2xl border border-[var(--nt-line)] bg-white/85 px-4 py-4 transition hover:border-emerald-700/30 hover:bg-white">
        <p class="font-display text-base font-semibold">Alertes</p>
        <p class="mt-1 text-sm text-nt-ink/55">Anomalies à vérifier</p>
    </a>
    <a href="{{ route('cold-rooms.index') }}" class="rounded-2xl border border-[var(--nt-line)] bg-white/85 px-4 py-4 transition hover:border-emerald-700/30 hover:bg-white">
        <p class="font-display text-base font-semibold">Chambres froides</p>
        <p class="mt-1 text-sm text-nt-ink/55">Occupation, T°, stock FEFO</p>
    </a>
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-2">
    <div class="nt-card">
        <div class="flex items-center justify-between gap-2">
            <h2 class="font-display text-xl font-semibold">Shipments récents</h2>
            <a href="{{ route('shipments.index') }}" class="nt-link text-sm">Tout voir</a>
        </div>
        <ul class="mt-4 divide-y divide-[var(--nt-line)] text-sm">
            @forelse ($recentShipments as $shipment)
                <li class="py-3">
                    <a href="{{ route('shipments.show', $shipment) }}" class="font-medium text-emerald-800">{{ $shipment->code }}</a>
                    <p class="text-nt-ink/55">
                        {{ $shipment->fromOrganization?->name ?: '—' }} → {{ $shipment->toOrganization?->name ?: '—' }}
                        · {{ $shipment->status?->label() ?? $shipment->status }}
                    </p>
                </li>
            @empty
                <li class="py-3 text-nt-ink/55">Aucun shipment. Créez-en un depuis la Control Tower.</li>
            @endforelse
        </ul>
    </div>

    <div class="nt-card">
        <div class="flex items-center justify-between gap-2">
            <h2 class="font-display text-xl font-semibold">Alertes ouvertes</h2>
            <a href="{{ route('alert-center.index') }}" class="nt-link text-sm">Alert Center</a>
        </div>
        <ul class="mt-4 divide-y divide-[var(--nt-line)] text-sm">
            @forelse ($recentAlerts as $anomaly)
                <li class="py-3">
                    <a href="{{ route('alert-center.show', $anomaly) }}" class="font-medium text-emerald-800">{{ $anomaly->title }}</a>
                    <p class="text-nt-ink/55">{{ $anomaly->severity->label() }} · {{ $anomaly->detected_at?->format('d/m H:i') }}</p>
                </li>
            @empty
                <li class="py-3 text-nt-ink/55">Aucune alerte active.</li>
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
</div>

{{-- Référentiel (secondary) --}}
<section class="mt-10 rounded-2xl border border-dashed border-[var(--nt-line)] bg-white/50 p-5">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="nt-kicker">Référentiel</p>
            <h2 class="mt-1 font-display text-lg font-semibold">Organisations, produits & lots</h2>
            <p class="text-sm text-nt-ink/55">Données de base — hors navigation principale.</p>
        </div>
    </div>
    <div class="mt-4 grid gap-3 sm:grid-cols-3">
        <a href="{{ route('organizations.index') }}" class="rounded-xl border border-[var(--nt-line)] bg-white px-4 py-3 text-sm transition hover:bg-nt-mist">
            <span class="font-medium">Organisations</span>
            <span class="mt-0.5 block text-nt-ink/50">{{ $organizationCount }} liées à votre compte</span>
        </a>
        <a href="{{ route('products.index') }}" class="rounded-xl border border-[var(--nt-line)] bg-white px-4 py-3 text-sm transition hover:bg-nt-mist">
            <span class="font-medium">Produits</span>
            <span class="mt-0.5 block text-nt-ink/50">{{ $productCount }} fiches</span>
        </a>
        <a href="{{ route('batches.index') }}" class="rounded-xl border border-[var(--nt-line)] bg-white px-4 py-3 text-sm transition hover:bg-nt-mist">
            <span class="font-medium">Lots & QR</span>
            <span class="mt-0.5 block text-nt-ink/50">{{ $batchCount }} lots</span>
        </a>
    </div>
</section>
@endsection
