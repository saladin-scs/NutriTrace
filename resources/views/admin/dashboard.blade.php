@extends('layouts.admin')

@section('title', 'Tableau de bord')

@section('content')
<div class="nt-reveal">
    <p class="nt-kicker">Back office</p>
    <h1 class="nt-page-title mt-1">Bonjour, {{ auth()->user()->name }}</h1>
    <p class="nt-page-sub">Pilotage plateforme + accès opérations.</p>
</div>

<div class="mt-6 flex flex-wrap gap-2">
    <a href="{{ route('control-tower.index') }}" class="nt-btn">Control Tower</a>
    <a href="{{ route('shipments.index') }}" class="nt-btn-secondary">Shipments</a>
    <a href="{{ route('alert-center.index') }}" class="nt-btn-secondary">Alertes</a>
    <a href="{{ route('cold-rooms.index') }}" class="nt-btn-secondary">Chambres froides</a>
</div>

<div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <a href="{{ route('shipments.index') }}" class="nt-stat block">
        <p class="nt-kicker">Shipments actifs</p>
        <p class="nt-stat-value">{{ $activeShipments }}</p>
    </a>
    <a href="{{ route('alert-center.index') }}" class="nt-stat block">
        <p class="nt-kicker">Alertes ouvertes</p>
        <p class="nt-stat-value {{ $openAlerts > 0 ? 'text-rose-700' : '' }}">{{ $openAlerts }}</p>
    </a>
    <a href="{{ route('alert-center.index', ['severity' => 'critical']) }}" class="nt-stat block">
        <p class="nt-kicker">Critiques</p>
        <p class="nt-stat-value {{ $criticalAlerts > 0 ? 'text-rose-700' : '' }}">{{ $criticalAlerts }}</p>
    </a>
    <a href="{{ route('cold-rooms.index') }}" class="nt-stat block">
        <p class="nt-kicker">Chambres froides</p>
        <p class="nt-stat-value">{{ $coldRoomCount }}</p>
    </a>
</div>

<div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="nt-stat"><p class="nt-kicker">Organisations</p><p class="nt-stat-value">{{ $organizationCount }}</p></div>
    <div class="nt-stat"><p class="nt-kicker">En attente</p><p class="nt-stat-value">{{ $pendingOrganizations }}</p></div>
    <div class="nt-stat"><p class="nt-kicker">Produits</p><p class="nt-stat-value">{{ $productCount }}</p></div>
    <div class="nt-stat"><p class="nt-kicker">Lots</p><p class="nt-stat-value">{{ $batchCount }}</p></div>
    <div class="nt-stat"><p class="nt-kicker">Flux CF</p><p class="nt-stat-value">{{ $movementCount }}</p></div>
    <div class="nt-stat"><p class="nt-kicker">Utilisateurs</p><p class="nt-stat-value">{{ $userCount }}</p></div>
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-2">
    <div class="nt-card">
        <div class="flex items-center justify-between gap-2">
            <h2 class="font-display text-lg font-semibold">Alertes récentes</h2>
            <a href="{{ route('alert-center.index') }}" class="nt-link text-sm">Tout voir</a>
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
        <h2 class="font-display text-lg font-semibold">Actions prioritaires</h2>
        <ul class="mt-4 space-y-3 text-sm">
            <li class="flex items-center justify-between gap-3 border-b border-[var(--nt-line)] pb-3">
                <span>Organisations à valider</span>
                <a href="{{ route('admin.organizations.index', ['status' => 'pending']) }}" class="font-medium text-emerald-800">{{ $pendingOrganizations }} →</a>
            </li>
            <li class="flex items-center justify-between gap-3 border-b border-[var(--nt-line)] pb-3">
                <span>Control Tower</span>
                <a href="{{ route('control-tower.index') }}" class="font-medium text-emerald-800">Ouvrir →</a>
            </li>
            <li class="flex items-center justify-between gap-3">
                <span>Front office</span>
                <a href="{{ route('dashboard') }}" class="font-medium text-emerald-800">Ouvrir →</a>
            </li>
        </ul>
    </div>
</div>
@endsection
