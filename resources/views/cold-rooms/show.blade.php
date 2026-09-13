@extends('layouts.front')

@section('title', $coldRoom->name)

@section('content')
<div class="flex flex-wrap items-start justify-between gap-4">
    <div>
        <p class="nt-kicker">Nœud de distribution</p>
        <h1 class="nt-page-title mt-1">{{ $coldRoom->name }}</h1>
        <p class="nt-page-sub">
            {{ $coldRoom->code }} · {{ $coldRoom->organization?->name }}
            · {{ $coldRoom->status->label() }}
        </p>
    </div>
    <a href="{{ route('cold-rooms.index') }}" class="nt-btn-secondary">Liste</a>
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-1">
        <div class="nt-card space-y-2 text-sm">
            <h2 class="font-display text-lg font-semibold">Fiche nœud</h2>
            <p>Capacité — {{ $coldRoom->capacity_kg ?: '—' }} kg</p>
            <p>Consigne — {{ $coldRoom->target_temp_min_c }}°C → {{ $coldRoom->target_temp_max_c }}°C</p>
            <p>Lieu — {{ $coldRoom->location?->city ?: '—' }}</p>
            <p>Responsable — {{ $coldRoom->responsible?->name ?: '—' }}</p>
            @if ($coldRoom->description)
                <p class="pt-2 text-nt-ink/70">{{ $coldRoom->description }}</p>
            @endif
        </div>

        @can('recordMovement', $coldRoom)
            <div class="nt-card">
                <h2 class="font-display text-lg font-semibold">Enregistrer un flux</h2>
                <p class="mt-1 text-xs text-nt-ink/45">Quand · Où · Qui · D’où · Vers où · Conditions · Durée · Événement</p>
                <form method="POST" action="{{ route('cold-rooms.movements.store', $coldRoom) }}" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label class="nt-label">Événement</label>
                        <select name="type" class="nt-field" required>
                            @foreach ($movementTypes as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="nt-label">Lot</label>
                        <select name="batch_id" class="nt-field">
                            <option value="">—</option>
                            @foreach ($batches as $batch)
                                <option value="{{ $batch->id }}">{{ $batch->code }} — {{ $batch->product?->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="nt-label">Quantité</label>
                            <input name="quantity" type="number" step="0.001" class="nt-field" required>
                        </div>
                        <div>
                            <label class="nt-label">T° (°C)</label>
                            <input name="temperature_c" type="number" step="0.1" class="nt-field" value="3">
                        </div>
                    </div>
                    <div>
                        <label class="nt-label">Humidité %</label>
                        <input name="humidity_pct" type="number" step="0.1" class="nt-field" value="85">
                    </div>
                    <div>
                        <label class="nt-label">Libellé événement</label>
                        <input name="event_label" class="nt-field" placeholder="Ex. Réception matin marché">
                    </div>
                    <div>
                        <label class="nt-label">Org. destination (sortie)</label>
                        <select name="to_organization_id" class="nt-field">
                            <option value="">—</option>
                            @foreach ($organizations as $organization)
                                <option value="{{ $organization->id }}">{{ $organization->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="nt-btn w-full" type="submit">Historiser le flux</button>
                </form>
            </div>
        @endcan
    </div>

    <div class="lg:col-span-2">
        <div class="nt-card">
            <h2 class="font-display text-lg font-semibold">Historique des flux</h2>
            <div class="mt-5 space-y-5">
                @forelse ($flows as $flow)
                    @php
                        $m = $flow['movement'];
                        $q = $flow['snapshot']->asQuestions();
                    @endphp
                    <article class="rounded-2xl border border-[var(--nt-line)] bg-[var(--nt-surface)]/60 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="font-medium">{{ $m->event_label ?: $m->type->label() }}</p>
                            <span class="text-xs text-nt-ink/45">{{ $m->occurred_at?->format('d/m/Y H:i') }}</span>
                        </div>
                        @if ($m->batch)
                            <p class="mt-1 text-sm text-emerald-800">
                                Lot <a href="{{ route('batches.show', $m->batch) }}" class="underline">{{ $m->batch->code }}</a>
                                · {{ $m->quantity }} {{ $m->unit }}
                            </p>
                        @endif
                        <dl class="mt-3 grid gap-2 text-xs sm:grid-cols-2">
                            @foreach ($q as $question => $answer)
                                <div>
                                    <dt class="uppercase tracking-wide text-nt-ink/40">{{ $question }}</dt>
                                    <dd class="mt-0.5 text-sm font-medium text-nt-ink/80">{{ $answer ?: '—' }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </article>
                @empty
                    <p class="text-sm text-nt-ink/55">Aucun flux encore. Enregistrez une entrée de lot pour démarrer la traçabilité.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
