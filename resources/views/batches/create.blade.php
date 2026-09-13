@extends('layouts.front')

@section('title', 'Nouveau lot')

@section('content')
<div class="max-w-xl">
    <h1 class="font-display text-3xl font-semibold">Nouveau lot</h1>
    <p class="mt-1 text-sm text-nt-ink/60">Produit : {{ $product->name }} ({{ $product->organization?->name }})</p>

    <form method="POST" action="{{ route('products.batches.store', $product) }}" class="nt-card mt-6 space-y-4">
        @csrf
        <div>
            <label class="nt-label" for="quantity">Quantité</label>
            <input id="quantity" name="quantity" type="number" step="0.001" min="0.001" class="nt-field" value="{{ old('quantity') }}" required>
            <x-input-error :messages="$errors->get('quantity')" />
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="nt-label" for="unit">Unité</label>
                <input id="unit" name="unit" class="nt-field" value="{{ old('unit', $product->unit) }}">
            </div>
            <div>
                <label class="nt-label" for="produced_at">Date de production</label>
                <input id="produced_at" name="produced_at" type="datetime-local" class="nt-field" value="{{ old('produced_at') }}">
            </div>
        </div>
        <div>
            <label class="nt-label" for="expires_at">Date limite</label>
            <input id="expires_at" name="expires_at" type="datetime-local" class="nt-field" value="{{ old('expires_at') }}">
        </div>
        <div>
            <label class="nt-label" for="production_location_id">Lieu de production</label>
            <select id="production_location_id" name="production_location_id" class="nt-field">
                <option value="">Siège org.</option>
                @foreach ($product->organization?->locations ?? [] as $location)
                    <option value="{{ $location->id }}" @selected(old('production_location_id') == $location->id)>
                        {{ $location->name }} — {{ $location->city }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="nt-label" for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="2" class="nt-field">{{ old('notes') }}</textarea>
        </div>
        <div class="flex gap-3">
            <button class="nt-btn" type="submit">Générer le lot + QR</button>
            <a href="{{ route('products.show', $product) }}" class="nt-btn-secondary">Annuler</a>
        </div>
    </form>
</div>
@endsection
