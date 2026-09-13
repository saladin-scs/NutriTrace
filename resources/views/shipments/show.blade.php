@extends('layouts.front')

@section('title', $shipment->code)

@section('content')
<div class="flex flex-wrap items-start justify-between gap-4">
    <div>
        <p class="nt-kicker">Shipment</p>
        <h1 class="nt-page-title mt-1">{{ $shipment->code }}</h1>
        <p class="nt-page-sub">{{ $shipment->status->label() }} · {{ $shipment->fromOrganization?->name }} → {{ $shipment->toOrganization?->name }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('control-tower.index') }}" class="nt-btn-secondary">Control Tower</a>
        @if (in_array($shipment->status->value, ['draft', 'delayed'], true))
            <form method="POST" action="{{ route('shipments.dispatch', $shipment) }}">
                @csrf
                <button class="nt-btn" type="submit">Expédier</button>
            </form>
        @endif
        @if ($shipment->status->isActive() || $shipment->status->value === 'arrived')
            <form method="POST" action="{{ route('shipments.receive', $shipment) }}">
                @csrf
                <button class="nt-btn" type="submit">Réceptionner</button>
            </form>
        @endif
    </div>
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-3">
    <div class="space-y-4 lg:col-span-1">
        <div class="nt-card space-y-2 text-sm">
            <h2 class="font-display text-lg font-semibold">Opération</h2>
            <p>Véhicule — {{ $shipment->vehicle?->registration ?: '—' }}</p>
            <p>Chauffeur — {{ $shipment->vehicle?->driver_name ?: '—' }}</p>
            <p>Charge — {{ $shipment->load_kg ?? $shipment->total_quantity }} {{ $shipment->unit }}</p>
            <p>ETA — {{ $shipment->eta_at?->format('d/m/Y H:i') ?: '—' }}</p>
            <p>Température — {{ $shipment->current_temperature_c !== null ? $shipment->current_temperature_c.'°C' : '—' }}</p>
            <p>CO₂e estimé — {{ $shipment->estimated_co2e_kg !== null ? $shipment->estimated_co2e_kg.' kg' : '—' }}</p>
            <p class="text-xs text-nt-ink/45">Estimation : distance × facteur émission × load factor</p>
        </div>
        <div class="nt-card space-y-2 text-sm">
            <h2 class="font-display text-lg font-semibold">Lots</h2>
            @foreach ($shipment->items as $item)
                <p>
                    <a class="text-emerald-800 underline" href="{{ route('batches.show', $item->batch) }}">{{ $item->batch?->code }}</a>
                    — {{ $item->batch?->product?->name }}
                    · {{ $item->quantity }} {{ $item->unit }}
                </p>
            @endforeach
        </div>
    </div>

    <div class="space-y-4 lg:col-span-2">
        <div class="nt-card">
            <h2 class="font-display text-lg font-semibold">Itinéraire</h2>
            @if ($panel['path'] ?? [])
                <ol class="mt-4 space-y-0">
                    @foreach ($panel['path'] as $index => $stop)
                        <li class="relative flex gap-4 pb-5 last:pb-0">
                            @if (! $loop->last)
                                <span class="absolute left-[0.65rem] top-6 h-[calc(100%-0.5rem)] w-px bg-emerald-800/20"></span>
                            @endif
                            <span class="relative z-10 mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-800 text-[10px] font-bold text-white">{{ $index + 1 }}</span>
                            <div>
                                <p class="font-medium">{{ $stop['label'] ?? 'Stop' }}</p>
                                <p class="text-xs text-nt-ink/45">{{ $stop['lat'] }}, {{ $stop['lng'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
                <p class="mt-4 text-sm text-nt-ink/55">Distance route — {{ $shipment->route?->distance_km ?? '—' }} km · Statut route — {{ $shipment->route?->status?->label() ?? '—' }}</p>
            @else
                <p class="mt-3 text-sm text-nt-ink/45">Aucun waypoint géolocalisé.</p>
            @endif
        </div>

        @if ($shipment->positions->isNotEmpty())
            <div class="nt-card">
                <h2 class="font-display text-lg font-semibold">Tracking</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($shipment->positions->take(8) as $position)
                        <li class="flex justify-between gap-3 border-b border-[var(--nt-line)]/60 py-2 last:border-0">
                            <span>{{ $position->latitude }}, {{ $position->longitude }}</span>
                            <span class="text-nt-ink/45">{{ $position->recorded_at?->format('d/m H:i') }} · {{ $position->speed_kmh }} km/h</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
@endsection
