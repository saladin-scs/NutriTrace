@extends('layouts.front')

@section('title', $anomaly->code)

@section('content')
<div class="flex flex-wrap items-start justify-between gap-4">
    <div>
        <p class="nt-kicker">{{ $anomaly->code }} · {{ $anomaly->severity->label() }}</p>
        <h1 class="nt-page-title mt-1">{{ $anomaly->title }}</h1>
        <p class="nt-page-sub">{{ $anomaly->category->label() }} · {{ $anomaly->status->label() }}</p>
    </div>
    <a href="{{ route('alert-center.index') }}" class="nt-btn-secondary">Retour</a>
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-3">
    <div class="space-y-4 lg:col-span-2">
        <div class="nt-card space-y-3 text-sm">
            <h2 class="font-display text-lg font-semibold">Message</h2>
            <p>{{ $anomaly->message }}</p>
            @if ($anomaly->recommendation)
                <div class="rounded-xl border border-[var(--nt-line)] bg-nt-mist/50 px-4 py-3">
                    <p class="text-xs uppercase tracking-wide text-nt-ink/40">Recommandation</p>
                    <p class="mt-1">{{ $anomaly->recommendation }}</p>
                </div>
            @endif
            <p class="text-xs text-nt-ink/40">Principe éthique : anomalie → preuve → vérification humaine. Pas d’accusation automatique.</p>
        </div>

        @if ($anomaly->evidence)
            <div class="nt-card">
                <h2 class="font-display text-lg font-semibold">Preuves / evidence</h2>
                <pre class="mt-3 overflow-x-auto rounded-xl bg-nt-mist/60 p-3 text-xs">{{ json_encode($anomaly->evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        @endif
    </div>

    <div class="space-y-4">
        <div class="nt-card space-y-2 text-sm">
            <h2 class="font-display text-lg font-semibold">Contexte</h2>
            <p>Détectée — {{ $anomaly->detected_at?->format('d/m/Y H:i') }}</p>
            <p>Métrique — {{ $anomaly->metric_value ?? '—' }} (seuil {{ $anomaly->threshold_value ?? '—' }})</p>
            @if ($anomaly->coldRoom)
                <p>Chambre — <a class="text-emerald-800 underline" href="{{ route('cold-rooms.show', $anomaly->coldRoom) }}">{{ $anomaly->coldRoom->code }}</a></p>
            @endif
            @if ($anomaly->shipment)
                <p>Shipment — <a class="text-emerald-800 underline" href="{{ route('shipments.show', $anomaly->shipment) }}">{{ $anomaly->shipment->code }}</a></p>
            @endif
            @if ($anomaly->batch)
                <p>Lot — <a class="text-emerald-800 underline" href="{{ route('batches.show', $anomaly->batch) }}">{{ $anomaly->batch->code }}</a></p>
            @endif
            <p>Organisation — {{ $anomaly->organization?->name ?: '—' }}</p>
            <p>Véhicule — {{ $anomaly->vehicle?->registration ?: '—' }}</p>
        </div>

        @can('update', $anomaly)
            <div class="nt-card">
                <h2 class="font-display text-lg font-semibold">Workflow</h2>
                <form method="POST" action="{{ route('alert-center.status', $anomaly) }}" class="mt-3 space-y-3">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="nt-label">Nouveau statut</label>
                        <select name="status" class="nt-field" required>
                            <option value="acknowledged">Prise en compte</option>
                            <option value="investigating">En investigation</option>
                            <option value="resolved">Résolue</option>
                            <option value="dismissed">Écartée</option>
                        </select>
                    </div>
                    <div>
                        <label class="nt-label">Note</label>
                        <textarea name="note" rows="2" class="nt-field" placeholder="Commentaire d’investigation…"></textarea>
                    </div>
                    <button class="nt-btn w-full" type="submit">Mettre à jour</button>
                </form>
            </div>
        @endcan
    </div>
</div>
@endsection
