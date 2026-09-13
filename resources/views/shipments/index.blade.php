@extends('layouts.front')

@section('title', 'Shipments')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="nt-kicker">Logistique</p>
        <h1 class="nt-page-title mt-1">Shipments</h1>
        <p class="nt-page-sub">Expéditions opérationnelles du réseau</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('control-tower.index') }}" class="nt-btn-secondary">Control Tower</a>
        <a href="{{ route('shipments.create') }}" class="nt-btn">Nouveau</a>
    </div>
</div>

<div class="nt-card mt-8 overflow-x-auto">
    <table class="w-full min-w-[720px] text-left text-sm">
        <thead class="border-b border-[var(--nt-line)] text-xs uppercase tracking-wide text-nt-ink/45">
            <tr>
                <th class="px-3 py-3 font-medium">Code</th>
                <th class="px-3 py-3 font-medium">Statut</th>
                <th class="px-3 py-3 font-medium">Trajet</th>
                <th class="px-3 py-3 font-medium">Véhicule</th>
                <th class="px-3 py-3 font-medium">Charge</th>
                <th class="px-3 py-3 font-medium"></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($shipments as $shipment)
                <tr class="border-b border-[var(--nt-line)]/70">
                    <td class="px-3 py-3 font-medium">{{ $shipment->code }}</td>
                    <td class="px-3 py-3">
                        <span class="rounded-full bg-nt-mist px-2.5 py-1 text-xs">{{ $shipment->status->label() }}</span>
                    </td>
                    <td class="px-3 py-3 text-nt-ink/70">
                        {{ $shipment->fromOrganization?->name ?: '—' }} → {{ $shipment->toOrganization?->name ?: '—' }}
                    </td>
                    <td class="px-3 py-3">{{ $shipment->vehicle?->registration ?: '—' }}</td>
                    <td class="px-3 py-3">{{ $shipment->load_kg ?? $shipment->total_quantity }} {{ $shipment->unit }}</td>
                    <td class="px-3 py-3 text-right">
                        <a href="{{ route('shipments.show', $shipment) }}" class="text-emerald-800 underline">Ouvrir</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-3 py-8 text-center text-nt-ink/45">Aucun shipment. Lancez le seeder Control Tower.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $shipments->links() }}</div>
@endsection
