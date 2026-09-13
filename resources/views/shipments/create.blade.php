@extends('layouts.front')

@section('title', 'Nouveau shipment')

@section('content')
<div>
    <p class="nt-kicker">Logistique</p>
    <h1 class="nt-page-title mt-1">Créer un shipment</h1>
</div>

<form method="POST" action="{{ route('shipments.store') }}" class="nt-card mt-8 max-w-3xl space-y-4">
    @csrf
    <div class="grid gap-4 sm:grid-cols-2">
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
            <input type="datetime-local" name="eta_at" class="nt-field">
        </div>
    </div>

    <div>
        <label class="nt-label">Lot</label>
        <select name="items[0][batch_id]" class="nt-field" required>
            @foreach ($batches as $batch)
                <option value="{{ $batch->id }}">{{ $batch->code }} — {{ $batch->product?->name }} ({{ $batch->quantity }} {{ $batch->unit }})</option>
            @endforeach
        </select>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="nt-label">Quantité</label>
            <input type="number" step="0.001" name="items[0][quantity]" class="nt-field" placeholder="Défaut = qté lot">
        </div>
        <div>
            <label class="nt-label">Notes</label>
            <input type="text" name="notes" class="nt-field">
        </div>
    </div>

    <button type="submit" class="nt-btn">Créer</button>
</form>
@endsection
