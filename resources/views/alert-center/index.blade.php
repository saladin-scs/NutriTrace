@extends('layouts.front')

@section('title', 'Alert Center')

@section('content')
@php $k = $payload['kpis']; @endphp

<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="nt-kicker">Decision support</p>
        <h1 class="nt-page-title mt-1">Alert Center</h1>
        <p class="nt-page-sub">Anomalie → preuve → vérification humaine</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('analytics.index') }}" class="nt-btn-secondary">Analytics</a>
        <form method="POST" action="{{ route('alert-center.scan') }}">
            @csrf
            <button class="nt-btn" type="submit">Scanner le réseau</button>
        </form>
    </div>
</div>

<div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
    <div class="rounded-2xl border border-[var(--nt-line)] bg-white/80 px-4 py-3">
        <p class="text-[11px] uppercase tracking-wide text-nt-ink/45">Ouvertes</p>
        <p class="mt-1 font-display text-2xl font-semibold">{{ $k['open'] }}</p>
    </div>
    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3">
        <p class="text-[11px] uppercase tracking-wide text-rose-700/70">Critiques</p>
        <p class="mt-1 font-display text-2xl font-semibold text-rose-800">{{ $k['critical'] }}</p>
    </div>
    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
        <p class="text-[11px] uppercase tracking-wide text-amber-800/70">Warnings</p>
        <p class="mt-1 font-display text-2xl font-semibold text-amber-900">{{ $k['warning'] }}</p>
    </div>
    <div class="rounded-2xl border border-[var(--nt-line)] bg-white/80 px-4 py-3">
        <p class="text-[11px] uppercase tracking-wide text-nt-ink/45">Info</p>
        <p class="mt-1 font-display text-2xl font-semibold">{{ $k['info'] }}</p>
    </div>
    <div class="rounded-2xl border border-[var(--nt-line)] bg-white/80 px-4 py-3">
        <p class="text-[11px] uppercase tracking-wide text-nt-ink/45">Résolues aujourd’hui</p>
        <p class="mt-1 font-display text-2xl font-semibold">{{ $k['resolved_today'] }}</p>
    </div>
</div>

<p class="mt-3 text-xs text-nt-ink/45">{{ $payload['disclaimer'] }}</p>

<form method="GET" class="nt-card mt-6 grid gap-3 sm:grid-cols-4">
    <div>
        <label class="nt-label">Sévérité</label>
        <select name="severity" class="nt-field">
            <option value="">Toutes</option>
            @foreach ($severities as $sev)
                <option value="{{ $sev->value }}" @selected(($filters['severity'] ?? '') === $sev->value)>{{ $sev->label() }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="nt-label">Catégorie</label>
        <select name="category" class="nt-field">
            <option value="">Toutes</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->value }}" @selected(($filters['category'] ?? '') === $cat->value)>{{ $cat->label() }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="nt-label">Statut</label>
        <select name="status" class="nt-field">
            <option value="">Actives</option>
            @foreach ($statuses as $st)
                <option value="{{ $st->value }}" @selected(($filters['status'] ?? '') === $st->value)>{{ $st->label() }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="nt-label">Recherche</label>
        <div class="flex gap-2">
            <input name="q" class="nt-field" value="{{ $filters['q'] ?? '' }}" placeholder="code, titre…">
            <button class="nt-btn-secondary shrink-0" type="submit">Filtrer</button>
        </div>
    </div>
</form>

<div class="mt-6 space-y-3">
    @forelse ($anomalies as $anomaly)
        <a href="{{ route('alert-center.show', $anomaly) }}"
           @class([
               'block rounded-2xl border px-4 py-4 transition hover:-translate-y-0.5',
               'border-rose-200 bg-rose-50/80' => $anomaly->severity->value === 'critical',
               'border-amber-200 bg-amber-50/70' => $anomaly->severity->value === 'warning',
               'border-[var(--nt-line)] bg-white/85' => $anomaly->severity->value === 'info',
           ])>
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide opacity-60">
                        {{ $anomaly->code }} · {{ $anomaly->category->label() }} · {{ $anomaly->severity->label() }}
                    </p>
                    <h2 class="mt-1 font-display text-lg font-semibold">{{ $anomaly->title }}</h2>
                    <p class="mt-1 text-sm text-nt-ink/70">{{ $anomaly->message }}</p>
                    <p class="mt-2 text-xs text-nt-ink/45">
                        {{ $anomaly->detected_at?->format('d/m/Y H:i') }}
                        @if ($anomaly->coldRoom) · {{ $anomaly->coldRoom->code }} @endif
                        @if ($anomaly->shipment) · {{ $anomaly->shipment->code }} @endif
                        @if ($anomaly->batch) · {{ $anomaly->batch->code }} @endif
                    </p>
                </div>
                <span class="rounded-full bg-white/80 px-3 py-1 text-xs font-medium">{{ $anomaly->status->label() }}</span>
            </div>
        </a>
    @empty
        <div class="nt-card text-sm text-nt-ink/55">
            Aucune alerte. Lancez un scan réseau ou attendez des mouvements stock / retards.
        </div>
    @endforelse
</div>

<div class="mt-6">{{ $anomalies->links() }}</div>
@endsection
