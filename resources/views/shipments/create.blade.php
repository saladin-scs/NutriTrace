@extends('layouts.front')

@section('title', 'Nouveau shipment')

@section('content')
<div>
    <p class="nt-kicker">Logistique</p>
    <h1 class="nt-page-title mt-1">Créer un shipment</h1>
    <p class="nt-page-sub">Déplacer des lots / produits d’un lieu à un autre.</p>
</div>

<form method="POST" action="{{ route('shipments.store') }}" class="nt-card mt-8 max-w-3xl space-y-5"
      x-data="shipmentCreateForm(@js($batches->map(fn ($b) => [
          'id' => $b->id,
          'label' => $b->code.' — '.($b->product?->name ?? 'Produit').' ('.$b->quantity.' '.($b->unit ?? 'kg').')',
          'quantity' => (float) $b->quantity,
          'unit' => $b->unit ?? 'kg',
      ])))">
    @csrf

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="nt-label">De (organisation)</label>
            <select name="from_organization_id" class="nt-field" required>
                <option value="">—</option>
                @foreach ($organizations as $org)
                    <option value="{{ $org->id }}" @selected(old('from_organization_id') == $org->id)>{{ $org->name }}</option>
                @endforeach
            </select>
            @error('from_organization_id') <p class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="nt-label">Vers (organisation)</label>
            <select name="to_organization_id" class="nt-field" required>
                <option value="">—</option>
                @foreach ($organizations as $org)
                    <option value="{{ $org->id }}" @selected(old('to_organization_id') == $org->id)>{{ $org->name }}</option>
                @endforeach
            </select>
            @error('to_organization_id') <p class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="nt-label">Nœud origine</label>
            <select name="origin_node_id" class="nt-field">
                <option value="">— (optionnel)</option>
                @foreach ($nodes as $node)
                    <option value="{{ $node->id }}">{{ $node->code }} — {{ $node->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="nt-label">Nœud destination</label>
            <select name="destination_node_id" class="nt-field">
                <option value="">— (optionnel)</option>
                @foreach ($nodes as $node)
                    <option value="{{ $node->id }}">{{ $node->code }} — {{ $node->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="nt-label">Canal</label>
            <select name="distribution_channel_id" class="nt-field">
                <option value="">—</option>
                @foreach ($channels as $channel)
                    <option value="{{ $channel->id }}">{{ $channel->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="nt-label">Véhicule</label>
            <select name="vehicle_id" class="nt-field">
                <option value="">—</option>
                @foreach ($vehicles as $vehicle)
                    <option value="{{ $vehicle->id }}">{{ $vehicle->registration }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="nt-label">Route</label>
            <select name="route_id" class="nt-field">
                <option value="">—</option>
                @foreach ($routes as $route)
                    <option value="{{ $route->id }}">{{ $route->code }} {{ $route->name ? '— '.$route->name : '' }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="nt-label">ETA</label>
            <input type="datetime-local" name="eta_at" class="nt-field" value="{{ old('eta_at') }}">
        </div>
    </div>

    <div class="border-t border-[var(--nt-line)] pt-4">
        <div class="flex items-center justify-between gap-2">
            <div>
                <h2 class="font-display text-base font-semibold">Lots / produits</h2>
                <p class="text-xs text-nt-ink/50">Ajoutez un ou plusieurs lots à déplacer.</p>
            </div>
            <button type="button" class="nt-btn-secondary !py-1.5 text-xs" @click="addRow()">+ Lot</button>
        </div>

        <div class="mt-3 space-y-3">
            <template x-for="(row, index) in rows" :key="row.key">
                <div class="grid gap-3 rounded-xl border border-[var(--nt-line)] bg-nt-mist/30 p-3 sm:grid-cols-[1fr_120px_90px_auto]">
                    <div>
                        <label class="nt-label">Lot</label>
                        <select class="nt-field" :name="`items[${index}][batch_id]`" x-model="row.batch_id" @change="onBatchChange(row)" required>
                            <option value="">—</option>
                            <template x-for="b in batches" :key="b.id">
                                <option :value="b.id" x-text="b.label"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="nt-label">Quantité</label>
                        <input type="number" step="0.001" min="0.001" class="nt-field"
                               :name="`items[${index}][quantity]`" x-model="row.quantity" required>
                    </div>
                    <div>
                        <label class="nt-label">Unité</label>
                        <input type="text" class="nt-field" :name="`items[${index}][unit]`" x-model="row.unit">
                    </div>
                    <div class="flex items-end">
                        <button type="button" class="text-xs text-rose-700 hover:underline" @click="removeRow(index)" x-show="rows.length > 1">Retirer</button>
                    </div>
                </div>
            </template>
        </div>
        @error('items') <p class="mt-2 text-xs text-rose-700">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="nt-label">Notes</label>
        <input type="text" name="notes" class="nt-field" value="{{ old('notes') }}">
    </div>

    <button type="submit" class="nt-btn">Créer le shipment</button>
</form>
@endsection
