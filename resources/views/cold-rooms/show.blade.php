@extends('layouts.front')

@section('title', $coldRoom->code.' — Digital Twin')

@section('content')
@php $k = $twin->kpis(); $r = $twin->room(); @endphp

<div class="flex flex-wrap items-start justify-between gap-4">
    <div>
        <p class="nt-kicker">Cold Chain Digital Twin</p>
        <h1 class="nt-page-title mt-1">{{ $coldRoom->code }}</h1>
        <p class="nt-page-sub">
            {{ $r['location'] ?? '—' }} · {{ $r['governorate'] ?? '' }}
            · {{ $r['operator'] ?? '' }}
            · {{ $coldRoom->type?->label() }}
        </p>
        @if (! empty($opsContext['batch']) || ! empty($opsContext['shipment']))
            <p class="mt-2 text-xs text-nt-ink/45">
                Contexte session —
                @if (! empty($opsContext['batch'])) Lot {{ $opsContext['batch'] }} @endif
                @if (! empty($opsContext['shipment'])) · Shipment {{ $opsContext['shipment'] }} @endif
            </p>
        @endif
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('control-tower.index') }}" class="nt-btn-secondary">Control Tower</a>
        <a href="{{ route('cold-rooms.index') }}" class="nt-btn-secondary">Liste</a>
    </div>
</div>

{{-- KPI strip --}}
<div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8">
    <div class="rounded-2xl border border-[var(--nt-line)] bg-white/80 px-4 py-3">
        <p class="text-[11px] uppercase tracking-wide text-nt-ink/45">Capacité</p>
        <p class="mt-1 font-display text-xl font-semibold">{{ number_format($k['capacity_kg'] ?? 0, 0, ',', ' ') }} kg</p>
    </div>
    <div class="rounded-2xl border border-[var(--nt-line)] bg-white/80 px-4 py-3">
        <p class="text-[11px] uppercase tracking-wide text-nt-ink/45">Stock actuel</p>
        <p class="mt-1 font-display text-xl font-semibold">{{ number_format($k['occupied_kg'] ?? 0, 0, ',', ' ') }} kg</p>
    </div>
    <div class="rounded-2xl border border-[var(--nt-line)] bg-white/80 px-4 py-3">
        <p class="text-[11px] uppercase tracking-wide text-nt-ink/45">Occupation</p>
        <p class="mt-1 font-display text-xl font-semibold {{ $twin->occupancyToneClass() }}">{{ $k['occupancy_pct'] ?? 0 }}%</p>
        <p class="text-[10px] text-nt-ink/40">{{ $twin->occupancyLabel() }}</p>
    </div>
    <div class="rounded-2xl border border-[var(--nt-line)] bg-white/80 px-4 py-3">
        <p class="text-[11px] uppercase tracking-wide text-nt-ink/45">Température</p>
        <p class="mt-1 font-display text-xl font-semibold {{ $twin->temperatureToneClass() }}">
            {{ $k['temperature_c'] !== null ? $k['temperature_c'].'°C' : '—' }}
        </p>
        <p class="text-[10px] text-nt-ink/40">{{ $k['temperature_status_label'] ?? '' }}</p>
    </div>
    <div class="rounded-2xl border border-[var(--nt-line)] bg-white/80 px-4 py-3">
        <p class="text-[11px] uppercase tracking-wide text-nt-ink/45">Entrées jour</p>
        <p class="mt-1 font-display text-xl font-semibold text-emerald-800">+{{ number_format($k['inbound_today_kg'] ?? 0, 0, ',', ' ') }}</p>
    </div>
    <div class="rounded-2xl border border-[var(--nt-line)] bg-white/80 px-4 py-3">
        <p class="text-[11px] uppercase tracking-wide text-nt-ink/45">Sorties jour</p>
        <p class="mt-1 font-display text-xl font-semibold">-{{ number_format($k['outbound_today_kg'] ?? 0, 0, ',', ' ') }}</p>
    </div>
    <div class="rounded-2xl border border-[var(--nt-line)] bg-white/80 px-4 py-3">
        <p class="text-[11px] uppercase tracking-wide text-nt-ink/45">Durée moy.</p>
        <p class="mt-1 font-display text-xl font-semibold">{{ $k['avg_storage_days'] ?? 0 }} j</p>
    </div>
    <div class="rounded-2xl border border-[var(--nt-line)] bg-white/80 px-4 py-3">
        <p class="text-[11px] uppercase tracking-wide text-nt-ink/45">Proche péremption</p>
        <p class="mt-1 font-display text-xl font-semibold text-amber-700">{{ number_format($k['near_expiry_kg'] ?? 0, 0, ',', ' ') }} kg</p>
    </div>
</div>

@if (count($twin->alerts()))
    <div class="mt-4 space-y-2">
        @foreach ($twin->alerts() as $alert)
            <div @class([
                'rounded-xl border px-4 py-3 text-sm',
                'border-rose-200 bg-rose-50 text-rose-900' => ($alert['severity'] ?? '') === 'critical',
                'border-amber-200 bg-amber-50 text-amber-900' => ($alert['severity'] ?? '') === 'warning',
                'border-[var(--nt-line)] bg-white/80 text-nt-ink/80' => ($alert['severity'] ?? '') === 'info',
            ])>
                <span class="text-xs font-semibold uppercase tracking-wide opacity-60">{{ $alert['category'] ?? 'ALERT' }}</span>
                <p class="mt-0.5">{{ $alert['message'] ?? '' }}</p>
            </div>
        @endforeach
        <p class="text-xs text-nt-ink/40">{{ $twin->disclaimer() }}</p>
    </div>
@endif

<div class="mt-8 grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-1">
        <div class="nt-card space-y-2 text-sm">
            <h2 class="font-display text-lg font-semibold">Fiche nœud</h2>
            <p>Opérateur — {{ $r['operator'] ?? '—' }}</p>
            <p>Propriétaire — {{ $r['owner'] ?? '—' }}</p>
            <p>Disponible — {{ number_format($k['available_kg'] ?? 0, 1, ',', ' ') }} kg</p>
            <p>Consigne — {{ $k['target_min_c'] }}°C → {{ $k['target_max_c'] }}°C</p>
            <p>Énergie — {{ $k['energy_kwh_day'] !== null ? $k['energy_kwh_day'].' kWh/j' : '—' }}</p>
            <p>Responsable — {{ $r['responsible'] ?? '—' }}</p>
        </div>

        @can('recordMovement', $coldRoom)
            <div class="nt-card">
                <h2 class="font-display text-lg font-semibold">Relevé température</h2>
                <form method="POST" action="{{ route('cold-rooms.temperature.store', $coldRoom) }}" class="mt-3 flex gap-2">
                    @csrf
                    <input name="temperature_c" type="number" step="0.1" class="nt-field" placeholder="Auto-capteur si vide">
                    <button class="nt-btn-secondary shrink-0" type="submit">Enregistrer</button>
                </form>
            </div>

            <div class="nt-card">
                <h2 class="font-display text-lg font-semibold">Enregistrer un flux</h2>
                <p class="mt-1 text-xs text-nt-ink/45">Qui · Quand · Où · D’où · Vers où · Combien · Pourquoi · Conditions</p>
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
                            <input name="temperature_c" type="number" step="0.1" class="nt-field" value="{{ $k['temperature_c'] ?? 3 }}">
                        </div>
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
                    <div>
                        <label class="nt-label">Libellé / motif</label>
                        <input name="event_label" class="nt-field" placeholder="Ex. Réception DC matin">
                    </div>
                    <button class="nt-btn w-full" type="submit">Historiser le flux</button>
                </form>
            </div>
        @endcan
    </div>

    <div class="space-y-6 lg:col-span-2">
        <div class="nt-card">
            <h2 class="font-display text-lg font-semibold">Stock ouvert (ledger)</h2>
            <p class="mt-1 text-sm text-nt-ink/55">Durée = maintenant − entrée (ou sortie − entrée)</p>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="border-b border-[var(--nt-line)] text-xs uppercase tracking-wide text-nt-ink/45">
                        <tr>
                            <th class="py-2 pr-3">Lot</th>
                            <th class="py-2 pr-3">Produit</th>
                            <th class="py-2 pr-3">Qté</th>
                            <th class="py-2 pr-3">Depuis</th>
                            <th class="py-2 pr-3">Durée</th>
                            <th class="py-2">Qui</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($twin->openStock() as $row)
                            <tr class="border-b border-[var(--nt-line)]/60">
                                <td class="py-2.5 pr-3 font-medium">
                                    @if (! empty($row['batch_id']))
                                        <a class="text-emerald-800 underline" href="{{ route('batches.show', $row['batch_id']) }}">{{ $row['batch_code'] }}</a>
                                    @else
                                        {{ $row['batch_code'] ?? '—' }}
                                    @endif
                                </td>
                                <td class="py-2.5 pr-3">{{ $row['product'] ?? '—' }}</td>
                                <td class="py-2.5 pr-3">{{ $row['remaining_quantity'] }} {{ $row['unit'] }}</td>
                                <td class="py-2.5 pr-3">{{ $row['stored_since'] ?? '—' }}</td>
                                <td class="py-2.5 pr-3">{{ $row['stored_for'] ?? '—' }}</td>
                                <td class="py-2.5">{{ $row['entered_by'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-6 text-center text-nt-ink/45">Aucun stock ouvert.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="nt-card">
            <h2 class="font-display text-lg font-semibold">File FEFO</h2>
            <p class="mt-1 text-sm text-nt-ink/55">First Expired, First Out — priorité de sortie</p>
            <ol class="mt-4 space-y-2 text-sm">
                @forelse (($twin->raw()['fefo_queue'] ?? []) as $i => $item)
                    <li class="flex items-center gap-3 rounded-xl border border-[var(--nt-line)] px-3 py-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-800 text-[10px] font-bold text-white">{{ $i + 1 }}</span>
                        <div>
                            <p class="font-medium">{{ $item['batch_code'] ?? '—' }} — {{ $item['product'] ?? '' }}</p>
                            <p class="text-xs text-nt-ink/45">{{ $item['remaining_quantity'] ?? '' }} · expire {{ $item['expires_at'] ?? 'n/a' }}</p>
                        </div>
                    </li>
                @empty
                    <li class="text-nt-ink/45">File vide.</li>
                @endforelse
            </ol>
        </div>

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
                    <p class="text-sm text-nt-ink/55">Aucun flux encore.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
