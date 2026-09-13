@extends('layouts.front')

@section('title', $batch->code)

@section('content')
<div class="flex flex-wrap items-start justify-between gap-4">
    <div>
        <p class="nt-kicker">Lot</p>
        <h1 class="nt-page-title mt-1">{{ $batch->code }}</h1>
        <p class="nt-page-sub">{{ $batch->product?->name }} · {{ $batch->organization?->name }} · {{ $batch->status->value }}</p>
    </div>
    <a href="{{ route('trace.show', $batch->code) }}" class="nt-btn" target="_blank">Passeport public</a>
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-1">
        <div class="nt-card text-center">
            <img src="{{ $qrImageUrl }}" alt="QR {{ $batch->code }}" class="mx-auto h-48 w-48 rounded-xl bg-white p-2" width="192" height="192">
            <p class="mt-3 break-all text-xs text-nt-ink/55">{{ $passportUrl }}</p>
        </div>
        <div class="nt-card space-y-2 text-sm">
            <h2 class="font-display text-lg font-semibold">Détails</h2>
            <p>Quantité — {{ $batch->quantity }} {{ $batch->unit }}</p>
            <p>Produit le — {{ $batch->produced_at?->format('d/m/Y H:i') ?: '—' }}</p>
            <p>Expire le — {{ $batch->expires_at?->format('d/m/Y') ?: '—' }}</p>
            <p>Lieu — {{ $batch->productionLocation?->city ?: '—' }}</p>
            @if ($batch->parentBatch)
                <p>Parent — <a class="text-emerald-800 underline" href="{{ route('batches.show', $batch->parentBatch) }}">{{ $batch->parentBatch->code }}</a></p>
            @endif
        </div>
    </div>

    <div class="space-y-6 lg:col-span-2">
        <div class="nt-card" x-data="{ selected: null }">
            <h2 class="font-display text-lg font-semibold">Timeline digitale</h2>
            <p class="mt-1 text-sm text-nt-ink/55">Historique événementiel du lot — cliquer pour le détail</p>
            <ol class="mt-4 space-y-0">
                @foreach ($batch->traceabilityEvents as $index => $event)
                    <li class="relative flex gap-4 pb-6 last:pb-0">
                        @if (! $loop->last)
                            <span class="absolute left-[0.65rem] top-6 h-[calc(100%-0.5rem)] w-px bg-emerald-800/20"></span>
                        @endif
                        <span class="relative z-10 mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-800 text-[10px] font-bold text-white">{{ $index + 1 }}</span>
                        <button type="button"
                                class="w-full rounded-xl text-left transition hover:bg-nt-mist/60"
                                @click="selected = selected === {{ $event->id }} ? null : {{ $event->id }}">
                            <p class="font-medium">{{ $event->occurred_at?->format('H:i') }} — {{ $event->title ?: $event->type->label() }}</p>
                            <p class="text-sm text-nt-ink/55">
                                {{ $event->organization?->name }}
                                @if ($event->location) · {{ $event->location->city }} @endif
                                · {{ $event->occurred_at?->format('d/m/Y') }}
                            </p>
                            <div x-cloak x-show="selected === {{ $event->id }}" class="mt-2 space-y-1 rounded-lg border border-[var(--nt-line)] bg-white/80 px-3 py-2 text-xs text-nt-ink/70">
                                <p>Type — {{ $event->type->label() }}</p>
                                <p>Acteur — {{ $event->actor?->name ?: '—' }}</p>
                                <p>Quantité — {{ $event->quantity !== null ? $event->quantity.' '.$event->unit : '—' }}</p>
                                @if (! empty($event->meta['shipment_code']))
                                    <p>Shipment — {{ $event->meta['shipment_code'] }}</p>
                                @endif
                                @if (! empty($event->meta['vehicle']))
                                    <p>Véhicule — {{ $event->meta['vehicle'] }}</p>
                                @endif
                                @if ($event->notes)
                                    <p>Notes — {{ $event->notes }}</p>
                                @endif
                            </div>
                        </button>
                    </li>
                @endforeach
            </ol>
        </div>

        <div class="nt-card">
            <h2 class="font-display text-lg font-semibold">Chaîne synthétique</h2>
            <ol class="mt-4 space-y-0">
                @foreach ($nodes as $index => $node)
                    <li class="relative flex gap-4 pb-6 last:pb-0">
                        @if (! $loop->last)
                            <span class="absolute left-[0.65rem] top-6 h-[calc(100%-0.5rem)] w-px bg-emerald-800/20"></span>
                        @endif
                        <span class="relative z-10 mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-800/80 text-[10px] font-bold text-white">{{ $index + 1 }}</span>
                        <div>
                            <p class="font-medium">{{ $node->label }}</p>
                            <p class="text-sm text-nt-ink/55">
                                {{ $node->organizationName }}
                                @if ($node->locationLabel) · {{ $node->locationLabel }} @endif
                                @if ($node->occurredAt) · {{ \Illuminate\Support\Carbon::parse($node->occurredAt)->format('d/m/Y H:i') }} @endif
                            </p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>

        @can('transform', $batch)
            @if ($batch->status->value !== 'transformed')
                <div class="nt-card">
                    <h2 class="font-display text-lg font-semibold">Transformer le lot</h2>
                    <form method="POST" action="{{ route('batches.transform', $batch) }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                        @csrf
                        <div class="sm:col-span-2">
                            <label class="nt-label">Organisation transformateur</label>
                            <select name="organization_id" class="nt-field" required>
                                @foreach ($organizations as $organization)
                                    <option value="{{ $organization->id }}">{{ $organization->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="nt-label">Procédé</label>
                            <input name="process_name" class="nt-field" placeholder="Ex. découpe, pasteurisation" required>
                        </div>
                        <div>
                            <label class="nt-label">Qté sortie</label>
                            <input name="output_quantity" type="number" step="0.001" class="nt-field" value="{{ $batch->quantity }}">
                        </div>
                        <div>
                            <label class="nt-label">Pertes</label>
                            <input name="loss_quantity" type="number" step="0.001" class="nt-field" value="0">
                        </div>
                        <div class="sm:col-span-2">
                            <button class="nt-btn" type="submit">Enregistrer la transformation</button>
                        </div>
                    </form>
                </div>
            @endif
        @endcan

        @can('distribute', $batch)
            <div class="nt-card">
                <h2 class="font-display text-lg font-semibold">Distribuer</h2>
                <form method="POST" action="{{ route('batches.distribute', $batch) }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                    @csrf
                    <div class="sm:col-span-2">
                        <label class="nt-label">Destinataire</label>
                        <select name="to_organization_id" class="nt-field" required>
                            @foreach ($targets as $organization)
                                @if ($organization->id !== $batch->organization_id)
                                    <option value="{{ $organization->id }}">{{ $organization->name }} ({{ $organization->type->label() }})</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="nt-label">Quantité</label>
                        <input name="quantity" type="number" step="0.001" class="nt-field" value="{{ $batch->quantity }}">
                    </div>
                    <div>
                        <label class="nt-label">Distance (km)</label>
                        <input name="distance_km" type="number" step="0.1" class="nt-field" placeholder="ex. 18">
                    </div>
                    <div>
                        <label class="nt-label">Transport</label>
                        <select name="transport_mode" class="nt-field">
                            @foreach ($transportModes as $mode)
                                <option value="{{ $mode->value }}">{{ $mode->value }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <button class="nt-btn" type="submit">Enregistrer la distribution</button>
                    </div>
                </form>
            </div>
        @endcan
    </div>
</div>
@endsection
