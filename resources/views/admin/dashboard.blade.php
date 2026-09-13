@extends('layouts.admin')

@section('title', 'Tableau de bord')

@section('content')
<div class="nt-reveal">
    <p class="nt-kicker">Back office</p>
    <h1 class="nt-page-title mt-1">Bonjour, {{ auth()->user()->name }}</h1>
    <p class="nt-page-sub">Pilotage de la plateforme NutriTrace.</p>
</div>

<div class="mt-6 flex flex-wrap gap-2">
    <a href="{{ route('admin.organizations.index') }}" class="nt-btn">Organisations</a>
    <a href="{{ route('admin.users.index') }}" class="nt-btn-secondary">Utilisateurs</a>
    <a href="{{ route('products.index') }}" class="nt-btn-secondary">Produits</a>
    <a href="{{ route('cold-rooms.index') }}" class="nt-btn-secondary">Chambres & flux</a>
</div>

<div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="nt-stat"><p class="nt-kicker">Organisations</p><p class="nt-stat-value">{{ $organizationCount }}</p></div>
    <div class="nt-stat"><p class="nt-kicker">En attente</p><p class="nt-stat-value">{{ $pendingOrganizations }}</p></div>
    <div class="nt-stat"><p class="nt-kicker">Produits</p><p class="nt-stat-value">{{ $productCount }}</p></div>
    <div class="nt-stat"><p class="nt-kicker">Lots</p><p class="nt-stat-value">{{ $batchCount }}</p></div>
    <div class="nt-stat"><p class="nt-kicker">Chambres froides</p><p class="nt-stat-value">{{ $coldRoomCount }}</p></div>
    <div class="nt-stat"><p class="nt-kicker">Flux CF</p><p class="nt-stat-value">{{ $movementCount }}</p></div>
    <div class="nt-stat"><p class="nt-kicker">Utilisateurs</p><p class="nt-stat-value">{{ $userCount }}</p></div>
</div>

<div class="mt-8 nt-card">
    <h2 class="font-display text-lg font-semibold">Actions prioritaires</h2>
    <ul class="mt-4 space-y-3 text-sm">
        <li class="flex items-center justify-between gap-3 border-b border-[var(--nt-line)] pb-3">
            <span>Organisations à valider</span>
            <a href="{{ route('admin.organizations.index', ['status' => 'pending']) }}" class="font-medium text-emerald-800">{{ $pendingOrganizations }} →</a>
        </li>
        <li class="flex items-center justify-between gap-3">
            <span>Front office</span>
            <a href="{{ route('dashboard') }}" class="font-medium text-emerald-800">Ouvrir →</a>
        </li>
    </ul>
</div>
@endsection
